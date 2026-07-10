<?php

namespace LaraGram\Console\Events;

use LaraGram\Console\Scheduling\Event;

class ScheduledTaskSkipped
{
    /**
     * Create a new event instance.
     *
     * @param  \LaraGram\Console\Scheduling\Event  $task  The scheduled event being run.
     */
    public function __construct(
        public Event $task,
    ) {
    }
}
