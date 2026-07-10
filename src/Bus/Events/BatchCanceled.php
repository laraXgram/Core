<?php

namespace LaraGram\Bus\Events;

use LaraGram\Bus\Batch;
use Throwable;

class BatchCanceled
{
    /**
     * Create a new event instance.
     *
     * @param  \LaraGram\Bus\Batch  $batch  The batch instance.
     * @param  \Throwable|null  $exception  The exception that caused the cancellation.
     */
    public function __construct(
        public Batch $batch,
        public ?Throwable $exception = null,
    ) {
    }
}
