<?php

namespace LaraGram\Console\Events;

use LaraGram\Console\Scheduling\Event;
use Throwable;

class ScheduledTaskFailed
{
    /**
     * The scheduled event that failed.
     *
     * @var \LaraGram\Console\Scheduling\Event
     */
    public $task;

    /**
     * The exception that was thrown.
     *
     * @var \Throwable
     */
    public $exception;

    /**
     * Create a new event instance.
     *
     * @param  \LaraGram\Console\Scheduling\Event  $task
     * @param  \Throwable  $exception
     * @return void
     */
    public function __construct(Event $task, Throwable $exception)
    {
        $this->task = $task;
        $this->exception = $exception;
    }
}
