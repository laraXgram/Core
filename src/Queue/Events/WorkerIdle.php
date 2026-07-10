<?php

namespace LaraGram\Queue\Events;

use LaraGram\Queue\WorkerOptions;

class WorkerIdle
{
    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName
     * @param  string  $queue
     * @param  \LaraGram\Queue\WorkerOptions  $workerOptions
     */
    public function __construct(
        public string $connectionName,
        public string $queue,
        public WorkerOptions $workerOptions,
    ) {
    }
}
