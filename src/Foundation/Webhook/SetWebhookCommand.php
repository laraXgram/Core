<?php

namespace LaraGram\Foundation\Webhook;

use LaraGram\Console\Command;
use LaraGram\Support\Facades\Request;

class SetWebhookCommand extends Command
{
    protected $signature = 'webhook:set';
    protected $description = 'Set Bot Webhook';

    public function handle()
    {
        if (in_array('-h', $this->arguments)){
            $this->output->message($this->description, true);
        }

        /** @var Request $request */
        $request = app('request');

        $result = $request->setWebhook($_ENV['BOT_DOMAIN']);

        if (!$result['ok']){
            $this->output->failed($result['description']);
        }else{
            $this->output->success($result['description']);
        }
    }
}