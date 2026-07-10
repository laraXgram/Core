<?php

namespace LaraGram\Bus;

use LaraGram\Container\Container;

trait Batchable
{
    /**
     * The batch ID (if applicable).
     *
     * @var string|null
     */
    public $batchId;

    /**
     * The fake batch, if applicable.
     *
     * @var \LaraGram\Support\Testing\Fakes\BatchFake
     */
    private $fakeBatch;

    /**
     * Get the batch instance for the job, if applicable.
     *
     * @return \LaraGram\Bus\Batch|null
     */
    public function batch()
    {
        if ($this->fakeBatch) {
            return $this->fakeBatch;
        }

        if ($this->batchId) {
            return Container::getInstance()->make(BatchRepository::class)?->find($this->batchId);
        }
    }

    /**
     * Determine if the batch is still active and processing.
     *
     * @return bool
     */
    public function batching()
    {
        $batch = $this->batch();

        return $batch && ! $batch->finished() && ! $batch->cancelled();
    }

    /**
     * Set the batch ID on the job.
     *
     * @param  string  $batchId
     * @return $this
     */
    public function withBatchId(string $batchId)
    {
        $this->batchId = $batchId;

        return $this;
    }
}
