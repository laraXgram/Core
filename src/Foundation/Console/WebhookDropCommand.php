<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Console\Attribute\AsCommand;
use LaraGram\Foundation\Console\Concerns\InteractsWithWebhooks;

#[AsCommand(name: 'webhook:drop')]
class WebhookDropCommand extends Command
{
    use InteractsWithWebhooks;

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
     * The webhook is set again with all of its settings, so the secret token
     * and allowed updates are kept.
     *
     * @return int
     */
    public function handle()
    {
        return $this->eachConnection(function (string $connection) {
            $result = $this->bot($connection)->setWebhook(...$this->webhookSettings($connection), drop_pending_updates: true);

            $result['ok']
                ? $this->components->info("[{$connection}] Pending updates dropped successfully!")
                : $this->components->error("[{$connection}] {$this->describe($result)}");

            return (bool) $result['ok'];
        });
    }
}
