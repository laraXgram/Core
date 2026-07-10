<?php

namespace LaraGram\Console\Events;

use LaraGram\Console\Scheduling\Event;

class ScheduledBackgroundTaskFinished
{
    /**
     * Create a new event instance.
     *
     * @param  \LaraGram\Console\Scheduling\Event  $task  The scheduled event that ran.
     */
    public function __construct(
        public Event $task,
    ) {
    }
}
