<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Console\Attribute\AsCommand;
use LaraGram\Foundation\Console\Concerns\InteractsWithWebhooks;

#[AsCommand(name: 'webhook:info')]
class WebhookInfoCommand extends Command
{
    use InteractsWithWebhooks;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'webhook:info';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show webhook information';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        return $this->eachConnection(function (string $connection) {
            $info = $this->bot($connection)->getWebhookInfo()['result'] ?? null;

            if ($info === null || $info['url'] == '') {
                $this->components->error("[{$connection}] Webhook not set");

                return false;
            }

            $this->components->twoColumnDetail("<fg=green;options=bold>{$connection}</>");

            foreach ($info as $key => $value) {
                if ($key == 'has_custom_certificate') {
                    $value = $value ? 'True' : 'False';
                }

                if (is_array($value)) {
                    $value = implode(', ', $value);
                }

                $this->components->twoColumnDetail("<fg=bright-blue;options=bold>$key:</>", "<fg=bright-blue;options=bold>$value</>");
            }

            $this->newLine();

            return true;
        });
    }
}
