<?php

namespace LaraGram\Foundation\Webhook;

use LaraGram\Console\Command;
use LaraGram\Support\Facades\Console;

class DeleteWebhookCommand extends Command
{
    protected $signature = 'webhook:delete';
    protected $description = 'Delete Bot Webhook';

    public function handle()
    {
        if ($this->getOption('h') == 'h') Console::output()->message($this->description, true);

        $result = request()->deleteWebhook();

        if (!$result['ok']){
            Console::output()->failed($result['description']);
        }else{
            Console::output()->success($result['description']);
        }
    }
}