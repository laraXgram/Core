<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Console\Attribute\AsCommand;

#[AsCommand(name: 'webhook:delete')]
class WebhookDeleteCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'webhook:delete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete bot webhook';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $result = request()->deleteWebhook();

        if (!$result['ok']){
            $this->components->error($result['description']);
        }else{
            $this->components->info($result['description']);
        }
    }
}