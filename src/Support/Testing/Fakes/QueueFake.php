<?php

namespace LaraGram\Support\Testing\Fakes;

use BadMethodCallException;
use Closure;
use LaraGram\Contracts\Queue\Queue;
use LaraGram\Queue\CallQueuedClosure;
use LaraGram\Queue\QueueManager;
use LaraGram\Support\Collection;
use LaraGram\Support\Traits\ReflectsClosures;

class QueueFake extends QueueManager implements Queue
{
    use ReflectsClosures;

    /**
     * The original queue manager.
     *
     * @var \LaraGram\Contracts\Queue\Queue
     */
    public $queue;

    /**
     * The job types that should be intercepted instead of pushed to the queue.
     *
     * @var \LaraGram\Support\Collection
     */
    protected $jobsToFake;

    /**
     * The job types that should be pushed to the queue and not intercepted.
     *
     * @var \LaraGram\Support\Collection
     */
    protected $jobsToBeQueued;

    /**
     * All of the jobs that have been pushed.
     *
     * @var array
     */
    protected $jobs = [];

    /**
     * Indicates if items should be serialized and restored when pushed to the queue.
     *
     * @var bool
     */
    protected bool $serializeAndRestore = false;

    /**
     * Create a new fake queue instance.
     *
     * @param  \LaraGram\Contracts\Foundation\Application  $app
     * @param  array  $jobsToFake
     * @param  \LaraGram\Queue\QueueManager|null  $queue
     * @return void
     */
    public function __construct($app, $jobsToFake = [], $queue = null)
    {
        parent::__construct($app);

        $this->jobsToFake = Collection::wrap($jobsToFake);
        $this->jobsToBeQueued = new Collection;
        $this->queue = $queue;
    }

    /**
     * Specify the jobs that should be queued instead of faked.
     *
     * @param  array|string  $jobsToBeQueued
     * @return $this
     */
    public function except($jobsToBeQueued)
    {
        $this->jobsToBeQueued = Collection::wrap($jobsToBeQueued)->merge($this->jobsToBeQueued);

        return $this;
    }

    /**
     * Determine if the given chain is entirely composed of objects.
     *
     * @param  array  $chain
     * @return bool
     */
    protected function isChainOfObjects($chain)
    {
        return ! (new Collection($chain))->contains(fn ($job) => ! is_object($job));
    }

    /**
     * Get all of the jobs matching a truth-test callback.
     *
     * @param  string  $job
     * @param  callable|null  $callback
     * @return \LaraGram\Support\Collection
     */
    public function pushed($job, $callback = null)
    {
        if (! $this->hasPushed($job)) {
            return new Collection;
        }

        $callback = $callback ?: fn () => true;

        return (new Collection($this->jobs[$job]))->filter(
            fn ($data) => $callback($data['job'], $data['queue'], $data['data'])
        )->pluck('job');
    }

    /**
     * Determine if there are any stored jobs for a given class.
     *
     * @param  string  $job
     * @return bool
     */
    public function hasPushed($job)
    {
        return isset($this->jobs[$job]) && ! empty($this->jobs[$job]);
    }

    /**
     * Resolve a queue connection instance.
     *
     * @param  mixed  $value
     * @return \LaraGram\Contracts\Queue\Queue
     */
    public function connection($value = null)
    {
        return $this;
    }

    /**
     * Get the size of the queue.
     *
     * @param  string|null  $queue
     * @return int
     */
    public function size($queue = null)
    {
        return (new Collection($this->jobs))->flatten(1)->filter(
            fn ($job) => $job['queue'] === $queue
        )->count();
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  string|object  $job
     * @param  mixed  $data
     * @param  string|null  $queue
     * @return mixed
     */
    public function push($job, $data = '', $queue = null)
    {
        if ($this->shouldFakeJob($job)) {
            if ($job instanceof Closure) {
                $job = CallQueuedClosure::create($job);
            }

            $this->jobs[is_object($job) ? get_class($job) : $job][] = [
                'job' => $this->serializeAndRestore ? $this->serializeAndRestoreJob($job) : $job,
                'queue' => $queue,
                'data' => $data,
            ];
        } else {
            is_object($job) && isset($job->connection)
                ? $this->queue->connection($job->connection)->push($job, $data, $queue)
                : $this->queue->push($job, $data, $queue);
        }
    }

    /**
     * Determine if a job should be faked or actually dispatched.
     *
     * @param  object  $job
     * @return bool
     */
    public function shouldFakeJob($job)
    {
        if ($this->shouldDispatchJob($job)) {
            return false;
        }

        if ($this->jobsToFake->isEmpty()) {
            return true;
        }

        return $this->jobsToFake->contains(
            fn ($jobToFake) => $job instanceof ((string) $jobToFake) || $job === (string) $jobToFake
        );
    }

    /**
     * Determine if a job should be pushed to the queue instead of faked.
     *
     * @param  object  $job
     * @return bool
     */
    protected function shouldDispatchJob($job)
    {
        if ($this->jobsToBeQueued->isEmpty()) {
            return false;
        }

        return $this->jobsToBeQueued->contains(
            fn ($jobToQueue) => $job instanceof ((string) $jobToQueue)
        );
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param  string  $payload
     * @param  string|null  $queue
     * @param  array  $options
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        //
    }

    /**
     * Push a new job onto the queue after (n) seconds.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  string|object  $job
     * @param  mixed  $data
     * @param  string|null  $queue
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        return $this->push($job, $data, $queue);
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  string  $queue
     * @param  string|object  $job
     * @param  mixed  $data
     * @return mixed
     */
    public function pushOn($queue, $job, $data = '')
    {
        return $this->push($job, $data, $queue);
    }

    /**
     * Push a new job onto a specific queue after (n) seconds.
     *
     * @param  string  $queue
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  string|object  $job
     * @param  mixed  $data
     * @return mixed
     */
    public function laterOn($queue, $delay, $job, $data = '')
    {
        return $this->push($job, $data, $queue);
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string|null  $queue
     * @return \LaraGram\Contracts\Queue\Job|null
     */
    public function pop($queue = null)
    {
        //
    }

    /**
     * Push an array of jobs onto the queue.
     *
     * @param  array  $jobs
     * @param  mixed  $data
     * @param  string|null  $queue
     * @return mixed
     */
    public function bulk($jobs, $data = '', $queue = null)
    {
        foreach ($jobs as $job) {
            $this->push($job, $data, $queue);
        }
    }

    /**
     * Get the jobs that have been pushed.
     *
     * @return array
     */
    public function pushedJobs()
    {
        return $this->jobs;
    }

    /**
     * Specify if jobs should be serialized and restored when being "pushed" to the queue.
     *
     * @param  bool  $serializeAndRestore
     * @return $this
     */
    public function serializeAndRestore(bool $serializeAndRestore = true)
    {
        $this->serializeAndRestore = $serializeAndRestore;

        return $this;
    }

    /**
     * Serialize and unserialize the job to simulate the queueing process.
     *
     * @param  mixed  $job
     * @return mixed
     */
    protected function serializeAndRestoreJob($job)
    {
        return unserialize(serialize($job));
    }

    /**
     * Get the connection name for the queue.
     *
     * @return string
     */
    public function getConnectionName()
    {
        //
    }

    /**
     * Set the connection name for the queue.
     *
     * @param  string  $name
     * @return $this
     */
    public function setConnectionName($name)
    {
        return $this;
    }

    /**
     * Override the QueueManager to prevent circular dependency.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        throw new BadMethodCallException(sprintf(
            'Call to undefined method %s::%s()', static::class, $method
        ));
    }
}
