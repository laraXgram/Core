<?php

namespace LaraGram\Foundation\Webhook;

use LaraGram\Console\Command;
use LaraGram\Support\Facades\Request;

class DeleteWebhookCommand extends Command
{
    protected $signature = 'webhook:delete';
    protected $description = 'Delete Bot Webhook';

    public function handle()
    {
        if ($this->getOption('h') == 'h') $this->output->message($this->description, true);

        /** @var Request $request */
        $request = app('request');

        $result = $request->deleteWebhook();

        if (!$result['ok']){
            $this->output->failed($result['description']);
        }else{
            $this->output->success($result['description']);
        }
    }
}