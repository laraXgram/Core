<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Console\Attribute\AsCommand;

#[AsCommand(name: 'webhook:info')]
class WebhookInfoCommand extends Command
{
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
     * @return void
     */
    public function handle(): void
    {
        $info = request()->getWebhookInfo()['result'];

        if ($info["url"] == ''){
            $this->components->error("Webhook not set");
            return;
        }

        foreach ($info as $key => $value) {
            if ($key == 'has_custom_certificate') $value = $value ? "True" : "False";
            $this->components->twoColumnDetail("<fg=bright-blue;options=bold>$key:</>", "<fg=bright-blue;options=bold>$value</>");
        }
    }
}