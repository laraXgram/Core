<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Console\Attribute\AsCommand;

#[AsCommand(name: 'webhook:drop')]
class WebhookDropCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'webhook:drop';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Drop webhook pending updates';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $result = request()->setWebhook(config('bot.bot.domain'), drop_pending_updates: true);

        if (!$result['ok']){
            $this->components->error($result['description']);
        }else{
            $this->components->info("Pending updates dropped successfully!");
        }
    }
}