<?php

namespace LaraGram\Bus\Events;

use LaraGram\Bus\Batch;

class BatchDispatched
{
    /**
     * The batch instance.
     *
     * @var \LaraGram\Bus\Batch
     */
    public $batch;

    /**
     * Create a new event instance.
     *
     * @param  \LaraGram\Bus\Batch  $batch
     * @return void
     */
    public function __construct(Batch $batch)
    {
        $this->batch = $batch;
    }
}
