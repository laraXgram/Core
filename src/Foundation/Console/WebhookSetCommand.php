<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Console\Attribute\AsCommand;
use LaraGram\Foundation\Console\Concerns\InteractsWithWebhooks;

#[AsCommand(name: 'webhook:set')]
class WebhookSetCommand extends Command
{
    use InteractsWithWebhooks;

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
     * @return int
     */
    public function handle()
    {
        return $this->eachConnection(function (string $connection) {
            $settings = $this->webhookSettings($connection);

            if (blank($settings['url'])) {
                $this->components->warn("[{$connection}] No webhook url is configured.");

                return false;
            }

            $result = $this->bot($connection)->setWebhook(...$settings);

            $result['ok']
                ? $this->components->info("[{$connection}] {$this->describe($result)}")
                : $this->components->error("[{$connection}] {$this->describe($result)}");

            return (bool) $result['ok'];
        });
    }
}
