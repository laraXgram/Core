<?php

namespace LaraGram\Foundation\Bus;

use LaraGram\Bus\DebounceLock;
use LaraGram\Bus\UniqueLock;
use LaraGram\Container\Container;
use LaraGram\Contracts\Bus\Dispatcher;
use LaraGram\Contracts\Cache\Repository as Cache;
use LaraGram\Contracts\Queue\PreparesForDispatch;
use LaraGram\Contracts\Queue\ShouldBeUnique;
use LaraGram\Queue\Attributes\DebounceFor;
use LaraGram\Queue\Attributes\ReadsQueueAttributes;
use LogicException;

class PendingDispatch
{
    use ReadsQueueAttributes;

    /**
     * The job.
     *
     * @var mixed
     */
    protected $job;

    /**
     * Indicates if the job should be dispatched immediately after sending the response.
     *
     * @var bool
     */
    protected $afterResponse = false;

    /**
     * Create a new pending job dispatch.
     *
     * @param  mixed  $job
     * @return void
     */
    public function __construct($job)
    {
        $this->job = $job;
    }

    /**
     * Set the desired connection for the job.
     *
     * @param  string|null  $connection
     * @return $this
     */
    public function onConnection($connection)
    {
        $this->job->onConnection($connection);

        return $this;
    }

    /**
     * Set the desired queue for the job.
     *
     * @param  string|null  $queue
     * @return $this
     */
    public function onQueue($queue)
    {
        $this->job->onQueue($queue);

        return $this;
    }

    /**
     * Set the desired connection for the chain.
     *
     * @param  string|null  $connection
     * @return $this
     */
    public function allOnConnection($connection)
    {
        $this->job->allOnConnection($connection);

        return $this;
    }

    /**
     * Set the desired queue for the chain.
     *
     * @param  string|null  $queue
     * @return $this
     */
    public function allOnQueue($queue)
    {
        $this->job->allOnQueue($queue);

        return $this;
    }

    /**
     * Set the desired delay in seconds for the job.
     *
     * @param  \DateTimeInterface|\DateInterval|int|null  $delay
     * @return $this
     */
    public function delay($delay)
    {
        $this->job->delay($delay);

        return $this;
    }

    /**
     * Indicate that the job should be dispatched after all database transactions have committed.
     *
     * @return $this
     */
    public function afterCommit()
    {
        $this->job->afterCommit();

        return $this;
    }

    /**
     * Indicate that the job should not wait until database transactions have been committed before dispatching.
     *
     * @return $this
     */
    public function beforeCommit()
    {
        $this->job->beforeCommit();

        return $this;
    }

    /**
     * Set the jobs that should run if this job is successful.
     *
     * @param  array  $chain
     * @return $this
     */
    public function chain($chain)
    {
        $this->job->chain($chain);

        return $this;
    }

    /**
     * Indicate that the job should be dispatched after the response is sent to the browser.
     *
     * @return $this
     */
    public function afterResponse()
    {
        $this->afterResponse = true;

        return $this;
    }

    /**
     * Determine if the job should be dispatched.
     *
     * @return bool
     */
    protected function shouldDispatch()
    {
        if ($this->job instanceof PreparesForDispatch &&
            $this->job->prepareForDispatch() === false) {
            return false;
        }

        if (! $this->job instanceof ShouldBeUnique) {
            return true;
        }

        return (new UniqueLock(Container::getInstance()->make(Cache::class)))
            ->acquire($this->job);
    }

    /**
     * Acquire a debounce lock for the job and set its delay.
     *
     * A debounced job waits for its quiet period before it runs, and every
     * dispatch within that period takes the lock over, so only the last one
     * is left to run.
     *
     * @return void
     *
     * @throws \LogicException
     */
    protected function acquireDebounceLock()
    {
        $debounceFor = $this->getAttributeValue($this->job, DebounceFor::class, 'debounceFor');

        if ($debounceFor === null) {
            return;
        }

        if ($this->job instanceof ShouldBeUnique) {
            throw new LogicException('A debounced job cannot also implement ShouldBeUnique.');
        }

        $result = (new DebounceLock(Container::getInstance()->make(Cache::class)))
            ->acquire($this->job, $debounceFor);

        $this->job->debounceOwner = $result['owner'];

        if (is_null($this->job->delay)) {
            $this->job->delay = $result['maxWaitExceeded'] ? 0 : $debounceFor;
        }
    }

    /**
     * Dynamically proxy methods to the underlying job.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return $this
     */
    public function __call($method, $parameters)
    {
        $this->job->{$method}(...$parameters);

        return $this;
    }

    /**
     * Handle the object's destruction.
     *
     * @return void
     */
    public function __destruct()
    {
        if (! $this->shouldDispatch()) {
            return;
        }

        $this->acquireDebounceLock();

        if ($this->afterResponse) {
            app(Dispatcher::class)->dispatchAfterResponse($this->job);
        } else {
            app(Dispatcher::class)->dispatch($this->job);
        }
    }
}
