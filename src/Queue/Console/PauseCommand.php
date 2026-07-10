<?php

namespace LaraGram\Queue\Console;

use LaraGram\Console\Command;
use LaraGram\Contracts\Queue\Factory as QueueManager;
use LaraGram\Queue\Console\Concerns\ParsesQueue;
use LaraGram\Queue\Worker;
use LaraGram\Console\Attribute\AsCommand;

#[AsCommand(name: 'queue:pause')]
class PauseCommand extends Command
{
    use ParsesQueue;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'queue:pause {queue : The name of the queue to pause}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pause job processing for a specific queue';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(QueueManager $manager)
    {
        [$connection, $queue] = $this->parseQueue($this->argument('queue'));

        if (! Worker::$pausable) {
            $this->components->error('Queue pausing is currently disabled.');

            return 1;
        }

        $manager->pause($connection, $queue);

        $this->components->info("Job processing on queue [{$connection}:{$queue}] has been paused.");

        return 0;
    }
}
