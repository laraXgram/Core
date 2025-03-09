<?php

namespace LaraGram\Queue\Console;

use LaraGram\Console\Command;
use LaraGram\Console\ConfirmableTrait;
use LaraGram\Contracts\Queue\ClearableQueue;
use LaraGram\Support\Str;
use ReflectionClass;
use LaraGram\Console\Attribute\AsCommand;
use LaraGram\Console\Input\InputArgument;
use LaraGram\Console\Input\InputOption;

#[AsCommand(name: 'queue:clear')]
class ClearCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'queue:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all of the jobs from the specified queue';

    /**
     * Execute the console command.
     *
     * @return int|null
     */
    public function handle()
    {
        if (! $this->confirmToProceed()) {
            return 1;
        }

        $connection = $this->argument('connection')
                        ?: $this->laragram['config']['queue.default'];

        // We need to get the right queue for the connection which is set in the queue
        // configuration file for the application. We will pull it based on the set
        // connection being run for the queue operation currently being executed.
        $queueName = $this->getQueue($connection);

        $queue = $this->laragram['queue']->connection($connection);

        if ($queue instanceof ClearableQueue) {
            $count = $queue->clear($queueName);

            $this->components->info('Cleared '.$count.' '.Str::plural('job', $count).' from the ['.$queueName.'] queue');
        } else {
            $this->components->error('Clearing queues is not supported on ['.(new ReflectionClass($queue))->getShortName().']');

            return 1;
        }

        return 0;
    }

    /**
     * Get the queue name to clear.
     *
     * @param  string  $connection
     * @return string
     */
    protected function getQueue($connection)
    {
        return $this->option('queue') ?: $this->laragram['config']->get(
            "queue.connections.{$connection}.queue", 'default'
        );
    }

    /**
     *  Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['connection', InputArgument::OPTIONAL, 'The name of the queue connection to clear'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['queue', null, InputOption::VALUE_OPTIONAL, 'The name of the queue to clear'],

            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production'],
        ];
    }
}
