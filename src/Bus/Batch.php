<?php

namespace LaraGram\Bus;

use Closure;
use LaraGram\Contracts\Queue\Factory as QueueFactory;
use LaraGram\Contracts\Support\Arrayable;
use LaraGram\Queue\CallQueuedClosure;
use LaraGram\Support\Arr;
use JsonSerializable;
use Throwable;

class Batch implements Arrayable, JsonSerializable
{
    protected $queue;

    protected $repository;

    /**
     * The batch ID.
     *
     * @var string
     */
    public $id;

    /**
     * The batch name.
     *
     * @var string
     */
    public $name;

    /**
     * The total number of jobs that belong to the batch.
     *
     * @var int
     */
    public $totalJobs;

    /**
     * The total number of jobs that are still pending.
     *
     * @var int
     */
    public $pendingJobs;

    /**
     * The total number of jobs that have failed.
     *
     * @var int
     */
    public $failedJobs;

    /**
     * The IDs of the jobs that have failed.
     *
     * @var array
     */
    public $failedJobIds;

    /**
     * The batch options.
     *
     * @var array
     */
    public $options;

    public $createdAt;

    public $cancelledAt;

    public $finishedAt;

    /**
     * Create a new batch instance.
     *
     * @param  \LaraGram\Contracts\Queue\Factory  $queue
     * @param  \LaraGram\Bus\BatchRepository  $repository
     * @param  string  $id
     * @param  string  $name
     * @param  int  $totalJobs
     * @param  int  $pendingJobs
     * @param  int  $failedJobs
     * @param  array  $failedJobIds
     * @param  array  $options
     * @param  string  $createdAt
     * @param  string|null  $cancelledAt
     * @param  string|null  $finishedAt
     * @return void
     */
    public function __construct(QueueFactory $queue,
                                BatchRepository $repository,
                                string $id,
                                string $name,
                                int $totalJobs,
                                int $pendingJobs,
                                int $failedJobs,
                                array $failedJobIds,
                                array $options,
                                string $createdAt,
                                ?string $cancelledAt = null,
                                ?string $finishedAt = null)
    {
        $this->queue = $queue;
        $this->repository = $repository;
        $this->id = $id;
        $this->name = $name;
        $this->totalJobs = $totalJobs;
        $this->pendingJobs = $pendingJobs;
        $this->failedJobs = $failedJobs;
        $this->failedJobIds = $failedJobIds;
        $this->options = $options;
        $this->createdAt = $createdAt;
        $this->cancelledAt = $cancelledAt;
        $this->finishedAt = $finishedAt;
    }

    /**
     * Get a fresh instance of the batch represented by this ID.
     *
     * @return self
     */
    public function fresh()
    {
        return $this->repository->find($this->id);
    }

    public function add($jobs)
    {
        $count = 0;

        if (!is_array($jobs)) {
            $jobs = [$jobs];
        }

        $jobsArray = [];

        foreach ($jobs as $job) {
            $job = $job instanceof Closure ? CallQueuedClosure::create($job) : $job;

            if (is_array($job)) {
                $count += count($job);

                $chain = $this->prepareBatchedChain($job);
                $firstJob = $chain[0];
                $firstJob->allOnQueue($this->options['queue'] ?? null);
                $firstJob->allOnConnection($this->options['connection'] ?? null);
                $remainingJobs = array_slice($chain, 1);
                $firstJob->chain($remainingJobs);

                $jobsArray[] = $firstJob;
            } else {
                $job->withBatchId($this->id);
                $count++;
                $jobsArray[] = $job;
            }
        }

        $this->repository->transaction(function () use ($jobsArray, $count) {
            $this->repository->incrementTotalJobs($this->id, $count);
            $this->queue->connection($this->options['connection'] ?? null)->bulk(
                $jobsArray,
                '',
                $this->options['queue'] ?? null
            );
        });

        return $this->fresh();
    }

    protected function prepareBatchedChain(array $chain)
    {
        $preparedChain = [];

        foreach ($chain as $job) {
            $job = $job instanceof Closure ? CallQueuedClosure::create($job) : $job;

            $preparedChain[] = $job->withBatchId($this->id);
        }

        return $preparedChain;
    }

    /**
     * Get the total number of jobs that have been processed by the batch thus far.
     *
     * @return int
     */
    public function processedJobs()
    {
        return $this->totalJobs - $this->pendingJobs;
    }

    /**
     * Get the percentage of jobs that have been processed (between 0-100).
     *
     * @return int
     */
    public function progress()
    {
        return $this->totalJobs > 0 ? round(($this->processedJobs() / $this->totalJobs) * 100) : 0;
    }

    /**
     * Record that a job within the batch finished successfully, executing any callbacks if necessary.
     *
     * @param  string  $jobId
     * @return void
     */
    public function recordSuccessfulJob(string $jobId)
    {
        $counts = $this->decrementPendingJobs($jobId);

        if ($this->hasProgressCallbacks()) {
            $batch = $this->fresh();

            foreach ($this->options['progress'] as $handler) {
                $this->invokeHandlerCallback($handler, $batch);
            }
        }

        if ($counts->pendingJobs === 0) {
            $this->repository->markAsFinished($this->id);
        }

        if ($counts->pendingJobs === 0 && $this->hasThenCallbacks()) {
            $batch = $this->fresh();

            foreach ($this->options['then'] as $handler) {
                $this->invokeHandlerCallback($handler, $batch);
            }
        }

        if ($counts->allJobsHaveRanExactlyOnce() && $this->hasFinallyCallbacks()) {
            $batch = $this->fresh();

            foreach ($this->options['finally'] as $handler) {
                $this->invokeHandlerCallback($handler, $batch);
            }
        }
    }


    public function decrementPendingJobs(string $jobId)
    {
        return $this->repository->decrementPendingJobs($this->id, $jobId);
    }

    /**
     * Determine if the batch has finished executing.
     *
     * @return bool
     */
    public function finished()
    {
        return ! is_null($this->finishedAt);
    }

    /**
     * Determine if the batch has "progress" callbacks.
     *
     * @return bool
     */
    public function hasProgressCallbacks()
    {
        return isset($this->options['progress']) && ! empty($this->options['progress']);
    }

    /**
     * Determine if the batch has "success" callbacks.
     *
     * @return bool
     */
    public function hasThenCallbacks()
    {
        return isset($this->options['then']) && ! empty($this->options['then']);
    }

    /**
     * Determine if the batch allows jobs to fail without cancelling the batch.
     *
     * @return bool
     */
    public function allowsFailures()
    {
        return Arr::get($this->options, 'allowFailures', false) === true;
    }

    /**
     * Determine if the batch has job failures.
     *
     * @return bool
     */
    public function hasFailures()
    {
        return $this->failedJobs > 0;
    }

    /**
     * Record that a job within the batch failed to finish successfully, executing any callbacks if necessary.
     *
     * @param  string  $jobId
     * @param  \Throwable  $e
     * @return void
     */
    public function recordFailedJob(string $jobId, $e)
    {
        $counts = $this->incrementFailedJobs($jobId);

        if ($counts->failedJobs === 1 && !$this->allowsFailures()) {
            $this->cancel();
        }

        if ($this->hasProgressCallbacks() && $this->allowsFailures()) {
            $batch = $this->fresh();

            foreach ($this->options['progress'] as $handler) {
                $this->invokeHandlerCallback($handler, $batch, $e);
            }
        }

        if ($counts->failedJobs === 1 && $this->hasCatchCallbacks()) {
            $batch = $this->fresh();

            foreach ($this->options['catch'] as $handler) {
                $this->invokeHandlerCallback($handler, $batch, $e);
            }
        }

        if ($counts->allJobsHaveRanExactlyOnce() && $this->hasFinallyCallbacks()) {
            $batch = $this->fresh();

            foreach ($this->options['finally'] as $handler) {
                $this->invokeHandlerCallback($handler, $batch, $e);
            }
        }
    }

    public function incrementFailedJobs(string $jobId)
    {
        return $this->repository->incrementFailedJobs($this->id, $jobId);
    }

    /**
     * Determine if the batch has "catch" callbacks.
     *
     * @return bool
     */
    public function hasCatchCallbacks()
    {
        return isset($this->options['catch']) && ! empty($this->options['catch']);
    }

    /**
     * Determine if the batch has "finally" callbacks.
     *
     * @return bool
     */
    public function hasFinallyCallbacks()
    {
        return isset($this->options['finally']) && ! empty($this->options['finally']);
    }

    /**
     * Cancel the batch.
     *
     * @return void
     */
    public function cancel()
    {
        $this->repository->cancel($this->id);
    }

    /**
     * Determine if the batch has been cancelled.
     *
     * @return bool
     */
    public function canceled()
    {
        return $this->cancelled();
    }

    /**
     * Determine if the batch has been cancelled.
     *
     * @return bool
     */
    public function cancelled()
    {
        return ! is_null($this->cancelledAt);
    }

    /**
     * Delete the batch from storage.
     *
     * @return void
     */
    public function delete()
    {
        $this->repository->delete($this->id);
    }

    protected function invokeHandlerCallback($handler, Batch $batch, ?Throwable $e = null)
    {
        try {
            return $handler($batch, $e);
        } catch (Throwable $e) {
        }
    }

    /**
     * Convert the batch to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'totalJobs' => $this->totalJobs,
            'pendingJobs' => $this->pendingJobs,
            'processedJobs' => $this->processedJobs(),
            'progress' => $this->progress(),
            'failedJobs' => $this->failedJobs,
            'options' => $this->options,
            'createdAt' => $this->createdAt,
            'cancelledAt' => $this->cancelledAt,
            'finishedAt' => $this->finishedAt,
        ];
    }

    /**
     * Get the JSON serializable representation of the object.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Dynamically access the batch's "options" via properties.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->options[$key] ?? null;
    }
}
