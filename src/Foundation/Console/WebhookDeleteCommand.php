<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Console\Attribute\AsCommand;
use LaraGram\Console\Input\InputOption;

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
        $connection = $this->option('connection') ?? config('bot.default');

        $result = app('request')
            ->connection($connection)
            ->deleteWebhook();

        if (!$result['ok']){
            $this->components->error($result['description']);
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
            ['connection', null, InputOption::VALUE_OPTIONAL, 'The bot connections for deleteWebhook', config('bot.default')],
        ];
    }
}