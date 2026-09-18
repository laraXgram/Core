<?php

namespace LaraGram\Broadcasting\Console;

use LaraGram\Broadcasting\BroadcastManager;
use LaraGram\Console\Attribute\AsCommand;
use LaraGram\Console\Command;

#[AsCommand(name: 'broadcast:cancel')]
class CancelCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'broadcast:cancel
                    {id : The broadcast identifier}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancel a running Telegram broadcast';

    /**
     * Execute the console command.
     *
     * @param  \LaraGram\Broadcasting\BroadcastManager  $manager
     * @return int
     */
    public function handle(BroadcastManager $manager)
    {
        if (! $manager->cancel($id = $this->argument('id'))) {
            $this->components->error("Broadcast [{$id}] was not found or has already finished.");

            return 1;
        }

        $this->components->info("Broadcast [{$id}] cancelled. Recipients not reached yet will be skipped.");

        return 0;
    }
}
