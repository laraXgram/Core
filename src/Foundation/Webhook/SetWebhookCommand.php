<?php

namespace LaraGram\Foundation\Webhook;

use LaraGram\Console\Command;
use LaraGram\Support\Facades\Config;
use LaraGram\Support\Facades\Console;

class SetWebhookCommand extends Command
{
    protected $signature = 'webhook:set';
    protected $description = 'Set Bot Webhook';

    public function handle()
    {
        if ($this->getOption('h') == 'h') Console::output()->message($this->description, true);

        $result = request()->setWebhook(Config::get('bot.BOT_DOMAIN'));

        if (!$result['ok']){
            Console::output()->failed($result['message']);
        }else{
            Console::output()->success($result['description']);
        }
    }
}