<?php

namespace LaraGram\Foundation\Webhook;

use LaraGram\Console\Command;
use LaraGram\Support\Facades\Console;
use LaraGram\Support\Facades\Request;

class DropWebhookCommand extends Command
{
    protected $signature = 'webhook:drop';
    protected $description = 'Drop Pending Updates';

    public function handle()
    {
        if ($this->getOption('h') == 'h') Console::output()->message($this->description, true);


        $result = request()->setWebhook($_ENV['BOT_DOMAIN'], drop_pending_updates: true);

        if (!$result['ok']){
            Console::output()->failed($result['description']);
        }else{
            Console::output()->success("Pending updates dropped successfully!");
        }
    }
}