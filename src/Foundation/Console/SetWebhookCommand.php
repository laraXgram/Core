<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Support\Facades\Console;

class SetWebhookCommand extends Command
{
    protected $signature = 'webhook:set';
    protected $description = 'Set Bot Webhook';

    public function handle()
    {
        if ($this->getOption('h') == 'h') Console::output()->message($this->description, true);

        $result = request()->setWebhook(config('bot.bot.domain'));

        if (!$result['ok']){
            Console::output()->failed($result['message']);
        }else{
            Console::output()->success($result['description']);
        }
    }
}