<?php

namespace LaraGram\Bus;

use LaraGram\Container\Container;
use LaraGram\Support\Testing\Fakes\BatchFake;

trait Batchable
{
    /**
     * The batch ID (if applicable).
     *
     * @var string
     */
    public $batchId;

    /**
     * The fake batch, if applicable.
     *
     * @var BatchFake
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

        return $batch && !$batch->cancelled();
    }

    /**
     * Set the batch ID on the job.
     *
     * @param string $batchId
     * @return $this
     */
    public function withBatchId(string $batchId)
    {
        $this->batchId = $batchId;

        return $this;
    }

    public function withFakeBatch(string  $id = '',
                                  string  $name = '',
                                  int     $totalJobs = 0,
                                  int     $pendingJobs = 0,
                                  int     $failedJobs = 0,
                                  array   $failedJobIds = [],
                                  array   $options = [],
                                  ?string $createdAt = null,
                                  ?string $cancelledAt = null,
                                  ?string $finishedAt = null)
    {
        $this->fakeBatch = new BatchFake(
            empty($id) ? $this->generateUuid() : $id,
            $name,
            $totalJobs,
            $pendingJobs,
            $failedJobs,
            $failedJobIds,
            $options,
            $createdAt ?? date('Y-m-d H:i:s'),
            $cancelledAt,
            $finishedAt,
        );

        return [$this, $this->fakeBatch];
    }

    private function generateUuid()
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('(%s-%s-%s-%s-%s)', str_split(bin2hex($data), 4));
    }
}
