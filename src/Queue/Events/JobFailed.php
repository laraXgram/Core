<?php

namespace LaraGram\Queue\Events;

class JobFailed
{
    /**
     * The connection name.
     *
     * @var string
     */
    public $connectionName;

    public $job;

    /**
     * The exception that caused the job to fail.
     *
     * @var \Throwable
     */
    public $exception;

    public function __construct($connectionName, $job, $exception)
    {
        $this->job = $job;
        $this->exception = $exception;
        $this->connectionName = $connectionName;
    }
}
