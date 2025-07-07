<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Console\Attribute\AsCommand;

#[AsCommand(name: 'webhook:set')]
class WebhookSetCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'webhook:set';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set bot webhook';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $result = request()->setWebhook(
            config('bot.bot.domain'),
            allowed_updates: ($allowed = config('bot.bot.allowed_updates')) == ['*'] ? null : $allowed,
            secret_token: config('bot.bot.secret_token') ?? null
        );

        if (!$result['ok']){
            $this->components->error($result['message']);
        }else{
            $this->components->info($result['description']);
        }
    }
}