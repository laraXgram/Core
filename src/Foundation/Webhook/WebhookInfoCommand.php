<?php

namespace LaraGram\Foundation\Webhook;

use LaraGram\Console\Command;
use LaraGram\Support\Facades\Request;

class WebhookInfoCommand extends Command
{
    protected $signature = 'webhook:info';
    protected $description = 'Get webhook information';

    public function handle()
    {
        if ($this->getOption('h') == 'h') $this->output->message($this->description, true);

        /** @var Request $request */
        $request = app('request');

        $info = $request->getWebhookInfo()['result'];

        if ($info["url"] == ''){
            $this->output->failed("Webhook not set", exit: true);
        }

        $url = "URL: " . str_replace('https://', '', str_replace('http://', '', $info['url']));
        $len = strlen($url);

        $pending_update = "Pending Updates: {$info['pending_update_count']}";
        $pending_update .= str_repeat(' ', $len - strlen($pending_update));

        $ip_address = "IP Address: {$info['ip_address']}";
        $ip_address .= str_repeat(' ', $len - strlen($ip_address));

        $certificate = "Certificate: {$info['has_custom_certificate']}";
        $certificate .= str_repeat(' ', $len - strlen($certificate));

        $max_connection = "Max Connections: {$info['max_connections']}";
        $max_connection .= str_repeat(' ', $len - strlen($max_connection));

        $this->output->message($url);
        $this->output->message($pending_update);
        $this->output->message($ip_address);
        $this->output->message($certificate);
        $this->output->message($max_connection);
    }
}