<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Support\Facades\Console;

class WebhookInfoCommand extends Command
{
    protected $signature = 'webhook:info';
    protected $description = 'Get webhook information';

    public function handle(): void
    {
        if ($this->getOption('h') == 'h') Console::output()->message($this->description, true);

        $info = request()->getWebhookInfo()['result'];

        if ($info["url"] == ''){
            Console::output()->failed("Webhook not set", exit: true);
        }

        $url = "URL: " . str_replace('https://', '', str_replace('http://', '', $info['url']));

        $pending_update = "Pending Updates: {$info['pending_update_count']}";
        $ip_address = "IP Address: {$info['ip_address']}";
        $certificate = "Certificate: " . ($info['has_custom_certificate'] ? 'Yes' : 'No');
        $max_connection = "Max Connections: {$info['max_connections']}";

        $maxLength = max(strlen($url), strlen($pending_update), strlen($ip_address), strlen($certificate), strlen($max_connection));

        $url = str_pad($url, $maxLength);
        $pending_update = str_pad($pending_update, $maxLength);
        $ip_address = str_pad($ip_address, $maxLength);
        $certificate = str_pad($certificate, $maxLength);
        $max_connection = str_pad($max_connection, $maxLength);

        Console::output()->message($url);
        Console::output()->message($pending_update);
        Console::output()->message($ip_address);
        Console::output()->message($certificate);
        Console::output()->message($max_connection, true);
    }
}