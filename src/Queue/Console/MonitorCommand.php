<?php

namespace LaraGram\Queue\Console;

use LaraGram\Console\Command;
use LaraGram\Contracts\Events\Dispatcher;
use LaraGram\Contracts\Queue\Factory;
use LaraGram\Queue\Events\QueueBusy;
use LaraGram\Support\Tempora;
use LaraGram\Support\Collection;
use LaraGram\Console\Attribute\AsCommand;

#[AsCommand(name: 'queue:monitor')]
class MonitorCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'queue:monitor
                       {queues : The names of the queues to monitor}
                       {--max=1000 : The maximum number of jobs that can be on the queue before an event is dispatched}
                       {--json : Output the queue size as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor the size of the specified queues';

    /**
     * The queue manager instance.
     *
     * @var \LaraGram\Contracts\Queue\Factory
     */
    protected $manager;

    /**
     * The events dispatcher instance.
     *
     * @var \LaraGram\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * Create a new queue monitor command.
     *
     * @param  \LaraGram\Contracts\Queue\Factory  $manager
     * @param  \LaraGram\Contracts\Events\Dispatcher  $events
     */
    public function __construct(Factory $manager, Dispatcher $events)
    {
        parent::__construct();

        $this->manager = $manager;
        $this->events = $events;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $queues = $this->parseQueues($this->argument('queues'));

        if ($this->option('json')) {
            $this->output->writeln((new Collection($queues))->map(function ($queue) {
                return array_merge($queue, [
                    'status' => str_contains($queue['status'], 'ALERT') ? 'ALERT' : 'OK',
                ]);
            })->toJson());
        } else {
            $this->displaySizes($queues);
        }

        $this->dispatchEvents($queues);
    }

    /**
     * Parse the queues into an array of the connections and queues.
     *
     * @param  string  $queues
     * @return \LaraGram\Support\Collection
     */
    protected function parseQueues($queues)
    {
        return (new Collection(explode(',', $queues)))->map(function ($queue) {
            [$connection, $queue] = array_pad(explode(':', $queue, 2), 2, null);

            if (! isset($queue)) {
                $queue = $connection;
                $connection = $this->laragram['config']['queue.default'];
            }

            return [
                'connection' => $connection,
                'queue' => $queue,
                'size' => $size = $this->manager->connection($connection)->size($queue),
                'pending' => $this->manager->connection($connection)->pendingSize($queue),
                'delayed' => $this->manager->connection($connection)->delayedSize($queue),
                'reserved' => $this->manager->connection($connection)->reservedSize($queue),
                'oldest_pending' => $this->manager->connection($connection)->creationTimeOfOldestPendingJob($queue),
                'status' => $size >= $this->option('max') ? '<fg=yellow;options=bold>ALERT</>' : '<fg=green;options=bold>OK</>',
            ];
        });
    }

    /**
     * Display the queue sizes in the console.
     *
     * @param  \LaraGram\Support\Collection  $queues
     * @return void
     */
    protected function displaySizes(Collection $queues)
    {
        $this->newLine();

        $this->components->twoColumnDetail('<fg=gray>Queue name</>', '<fg=gray>Size / Status</>');

        $queues->each(function ($queue) {
            $name = '['.$queue['connection'].'] '.$queue['queue'];
            $status = '['.$queue['size'].'] '.$queue['status'];

            $this->components->twoColumnDetail($name, $status);
            $this->components->twoColumnDetail('Pending jobs', $queue['pending'] ?? 'N/A');
            $this->components->twoColumnDetail('Delayed jobs', $queue['delayed'] ?? 'N/A');
            $this->components->twoColumnDetail('Reserved jobs', $queue['reserved'] ?? 'N/A');
            $this->components->twoColumnDetail('Oldest pending job', $queue['oldest_pending']
                ? Tempora::createFromTimestamp($queue['oldest_pending'])->diffForHumans()
                : 'N/A'
            );
            $this->line('');
        });

        $this->newLine();
    }

    /**
     * Fire the monitoring events.
     *
     * @param  \LaraGram\Support\Collection  $queues
     * @return void
     */
    protected function dispatchEvents(Collection $queues)
    {
        foreach ($queues as $queue) {
            if ($queue['status'] == '<fg=green;options=bold>OK</>') {
                continue;
            }

            $this->events->dispatch(
                new QueueBusy(
                    $queue['connection'],
                    $queue['queue'],
                    $queue['size'],
                )
            );
        }
    }
}
