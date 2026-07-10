<?php

namespace LaraGram\Queue\Events;

class JobDebounced
{
    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName
     * @param  \LaraGram\Contracts\Queue\Job  $job
     * @param  mixed  $command
     */
    public function __construct(
        public $connectionName,
        public $job,
        public $command,
    ) {
    }
}
