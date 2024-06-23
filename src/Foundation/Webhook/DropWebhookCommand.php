<?php

namespace LaraGram\Foundation\Webhook;

use LaraGram\Console\Command;
use LaraGram\Support\Facades\Request;

class DropWebhookCommand extends Command
{
    protected $signature = 'webhook:drop';
    protected $description = 'Drop Pending Updates';

    public function handle()
    {
        if (in_array('-h', $this->arguments)){
            $this->output->message($this->description, true);
        }

        /** @var Request $request */
        $request = app('request');

        $result = $request->setWebhook($_ENV['BOT_DOMAIN'], drop_pending_updates: true);

        if (!$result['ok']){
            $this->output->failed($result['description']);
        }else{
            $this->output->success("Pending updates dropped successfully!");
        }
    }
}