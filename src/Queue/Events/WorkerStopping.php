<?php

namespace LaraGram\Queue\Events;

class WorkerStopping
{
    /**
     * The worker exit status.
     *
     * @var int
     */
    public $status;

    /**
     * The worker options.
     *
     * @var \LaraGram\Queue\WorkerOptions|null
     */
    public $workerOptions;

    /**
     * Create a new event instance.
     *
     * @param  int  $status
     * @param  \LaraGram\Queue\WorkerOptions|null  $workerOptions
     * @return void
     */
    public function __construct($status = 0, $workerOptions = null)
    {
        $this->status = $status;
        $this->workerOptions = $workerOptions;
    }
}
