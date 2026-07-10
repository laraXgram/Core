<?php

namespace LaraGram\Bus\Events;

use LaraGram\Bus\Batch;

class BatchStarted
{
    /**
     * Create a new event instance.
     *
     * @param  \LaraGram\Bus\Batch  $batch  The batch instance.
     */
    public function __construct(
        public Batch $batch,
    ) {
    }
}
