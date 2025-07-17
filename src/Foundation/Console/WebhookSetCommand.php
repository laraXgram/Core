<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Console\Attribute\AsCommand;
use LaraGram\Console\Input\InputOption;

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
        $connection = $this->option('connection') ?? config('bot.default');

        $result = app('request')
            ->connection($connection)
            ->setWebhook(
                config('bot.connections.'.$connection.'.domain'),
                allowed_updates: ($allowed = config('bot.connections.'.$connection.'.allowed_updates')) == ['*'] ? null : $allowed,
                secret_token: config('bot.connections.'.$connection.'.secret_token') ?? null
            );

        if (!$result['ok']){
            $this->components->error($result['message']);
        }else{
            $this->components->info($result['description']);
        }
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['connection', null, InputOption::VALUE_OPTIONAL, 'The bot connections for setWebhook', config('bot.default')],
        ];
    }
}