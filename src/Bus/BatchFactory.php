<?php

namespace LaraGram\Bus;

use DateTimeImmutable;
use LaraGram\Contracts\Queue\Factory as QueueFactory;

class BatchFactory
{
    /**
     * The queue factory implementation.
     *
     * @var \LaraGram\Contracts\Queue\Factory
     */
    protected $queue;

    /**
     * Create a new batch factory instance.
     *
     * @param  \LaraGram\Contracts\Queue\Factory  $queue
     * @return void
     */
    public function __construct(QueueFactory $queue)
    {
        $this->queue = $queue;
    }

    /**
     * Create a new batch instance.
     *
     * @param  \LaraGram\Bus\BatchRepository  $repository
     * @param  string  $id
     * @param  string  $name
     * @param  int  $totalJobs
     * @param  int  $pendingJobs
     * @param  int  $failedJobs
     * @param  array  $failedJobIds
     * @param  array  $options
     * @param  DateTimeImmutable  $createdAt
     * @param  DateTimeImmutable|null  $cancelledAt
     * @param  DateTimeImmutable|null  $finishedAt
     * @return \LaraGram\Bus\Batch
     */
    public function make(BatchRepository $repository,
                         string $id,
                         string $name,
                         int $totalJobs,
                         int $pendingJobs,
                         int $failedJobs,
                         array $failedJobIds,
                         array $options,
                         DateTimeImmutable $createdAt,
                         ?DateTimeImmutable $cancelledAt,
                         ?DateTimeImmutable $finishedAt)
    {
        return new Batch($this->queue, $repository, $id, $name, $totalJobs, $pendingJobs, $failedJobs, $failedJobIds, $options, $createdAt, $cancelledAt, $finishedAt);
    }
}
