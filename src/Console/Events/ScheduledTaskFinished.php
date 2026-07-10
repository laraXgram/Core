<?php

namespace LaraGram\Console\Events;

use LaraGram\Console\Scheduling\Event;

class ScheduledTaskFinished
{
    /**
     * Create a new event instance.
     *
     * @param  \LaraGram\Console\Scheduling\Event  $task  The scheduled event that ran.
     * @param  float  $runtime  The runtime of the scheduled event.
     */
    public function __construct(
        public Event $task,
        public float $runtime,
    ) {
    }
}
