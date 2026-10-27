<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Console\Attribute\AsCommand;
use LaraGram\Foundation\Console\Concerns\InteractsWithWebhooks;

#[AsCommand(name: 'webhook:delete')]
class WebhookDeleteCommand extends Command
{
    use InteractsWithWebhooks;

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
     * @return int
     */
    public function handle()
    {
        return $this->eachConnection(function (string $connection) {
            $result = $this->bot($connection)->deleteWebhook();

            $result['ok']
                ? $this->components->info("[{$connection}] {$this->describe($result)}")
                : $this->components->error("[{$connection}] {$this->describe($result)}");

            return (bool) $result['ok'];
        });
    }
}
