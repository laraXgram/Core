<?php

namespace LaraGram\Support\Testing\Fakes;

use Closure;
use LaraGram\Bus\BatchRepository;
use LaraGram\Bus\ChainedBatch;
use LaraGram\Bus\PendingBatch;
use LaraGram\Contracts\Bus\QueueingDispatcher;
use LaraGram\Support\Arr;
use LaraGram\Support\Collection;
use LaraGram\Support\Traits\ReflectsClosures;

class BusFake implements Fake, QueueingDispatcher
{
    use ReflectsClosures;

    /**
     * The original Bus dispatcher implementation.
     *
     * @var \LaraGram\Contracts\Bus\QueueingDispatcher
     */
    public $dispatcher;

    /**
     * The job types that should be intercepted instead of dispatched.
     *
     * @var array
     */
    protected $jobsToFake = [];

    /**
     * The job types that should be dispatched instead of faked.
     *
     * @var array
     */
    protected $jobsToDispatch = [];

    /**
     * The fake repository to track batched jobs.
     *
     * @var \LaraGram\Bus\BatchRepository
     */
    protected $batchRepository;

    /**
     * The commands that have been dispatched.
     *
     * @var array
     */
    protected $commands = [];

    /**
     * The commands that have been dispatched synchronously.
     *
     * @var array
     */
    protected $commandsSync = [];

    /**
     * The commands that have been dispatched after the response has been sent.
     *
     * @var array
     */
    protected $commandsAfterResponse = [];

    /**
     * The batches that have been dispatched.
     *
     * @var array
     */
    protected $batches = [];

    /**
     * Indicates if commands should be serialized and restored when pushed to the Bus.
     *
     * @var bool
     */
    protected bool $serializeAndRestore = false;

    /**
     * Create a new bus fake instance.
     *
     * @param  \LaraGram\Contracts\Bus\QueueingDispatcher  $dispatcher
     * @param  array|string  $jobsToFake
     * @param  \LaraGram\Bus\BatchRepository|null  $batchRepository
     * @return void
     */
    public function __construct(QueueingDispatcher $dispatcher, $jobsToFake = [], ?BatchRepository $batchRepository = null)
    {
        $this->dispatcher = $dispatcher;
        $this->jobsToFake = Arr::wrap($jobsToFake);
        $this->batchRepository = $batchRepository ?: new BatchRepositoryFake;
    }

    /**
     * Specify the jobs that should be dispatched instead of faked.
     *
     * @param  array|string  $jobsToDispatch
     * @return $this
     */
    public function except($jobsToDispatch)
    {
        $this->jobsToDispatch = array_merge($this->jobsToDispatch, Arr::wrap($jobsToDispatch));

        return $this;
    }

    /**
     * Reset the chain properties to their default values on the job.
     *
     * @param  mixed  $job
     * @return mixed
     */
    protected function resetChainPropertiesToDefaults($job)
    {
        return tap(clone $job, function ($job) {
            $job->chainConnection = null;
            $job->chainQueue = null;
            $job->chainCatchCallbacks = null;
            $job->chained = [];
        });
    }

    /**
     * Create a new assertion about a chained batch.
     *
     * @param  \Closure  $callback
     * @return \LaraGram\Support\Testing\Fakes\ChainedBatchTruthTest
     */
    public function chainedBatch(Closure $callback)
    {
        return new ChainedBatchTruthTest($callback);
    }

    /**
     * Get all of the jobs matching a truth-test callback.
     *
     * @param  string  $command
     * @param  callable|null  $callback
     * @return \LaraGram\Support\Collection
     */
    public function dispatched($command, $callback = null)
    {
        if (! $this->hasDispatched($command)) {
            return new Collection;
        }

        $callback = $callback ?: fn () => true;

        return (new Collection($this->commands[$command]))->filter(fn ($command) => $callback($command));
    }

    /**
     * Get all of the jobs dispatched synchronously matching a truth-test callback.
     *
     * @param  string  $command
     * @param  callable|null  $callback
     * @return \LaraGram\Support\Collection
     */
    public function dispatchedSync(string $command, $callback = null)
    {
        if (! $this->hasDispatchedSync($command)) {
            return new Collection;
        }

        $callback = $callback ?: fn () => true;

        return (new Collection($this->commandsSync[$command]))->filter(fn ($command) => $callback($command));
    }

    /**
     * Get all of the jobs dispatched after the response was sent matching a truth-test callback.
     *
     * @param  string  $command
     * @param  callable|null  $callback
     * @return \LaraGram\Support\Collection
     */
    public function dispatchedAfterResponse(string $command, $callback = null)
    {
        if (! $this->hasDispatchedAfterResponse($command)) {
            return new Collection;
        }

        $callback = $callback ?: fn () => true;

        return (new Collection($this->commandsAfterResponse[$command]))->filter(fn ($command) => $callback($command));
    }

    /**
     * Get all of the pending batches matching a truth-test callback.
     *
     * @param  callable  $callback
     * @return \LaraGram\Support\Collection
     */
    public function batched(callable $callback)
    {
        if (empty($this->batches)) {
            return new Collection;
        }

        return (new Collection($this->batches))->filter(fn ($batch) => $callback($batch));
    }

    /**
     * Determine if there are any stored commands for a given class.
     *
     * @param  string  $command
     * @return bool
     */
    public function hasDispatched($command)
    {
        return isset($this->commands[$command]) && ! empty($this->commands[$command]);
    }

    /**
     * Determine if there are any stored commands for a given class.
     *
     * @param  string  $command
     * @return bool
     */
    public function hasDispatchedSync($command)
    {
        return isset($this->commandsSync[$command]) && ! empty($this->commandsSync[$command]);
    }

    /**
     * Determine if there are any stored commands for a given class.
     *
     * @param  string  $command
     * @return bool
     */
    public function hasDispatchedAfterResponse($command)
    {
        return isset($this->commandsAfterResponse[$command]) && ! empty($this->commandsAfterResponse[$command]);
    }

    /**
     * Dispatch a command to its appropriate handler.
     *
     * @param  mixed  $command
     * @return mixed
     */
    public function dispatch($command)
    {
        if ($this->shouldFakeJob($command)) {
            $this->commands[get_class($command)][] = $this->getCommandRepresentation($command);
        } else {
            return $this->dispatcher->dispatch($command);
        }
    }

    /**
     * Dispatch a command to its appropriate handler in the current process.
     *
     * Queueable jobs will be dispatched to the "sync" queue.
     *
     * @param  mixed  $command
     * @param  mixed  $handler
     * @return mixed
     */
    public function dispatchSync($command, $handler = null)
    {
        if ($this->shouldFakeJob($command)) {
            $this->commandsSync[get_class($command)][] = $this->getCommandRepresentation($command);
        } else {
            return $this->dispatcher->dispatchSync($command, $handler);
        }
    }

    /**
     * Dispatch a command to its appropriate handler in the current process.
     *
     * @param  mixed  $command
     * @param  mixed  $handler
     * @return mixed
     */
    public function dispatchNow($command, $handler = null)
    {
        if ($this->shouldFakeJob($command)) {
            $this->commands[get_class($command)][] = $this->getCommandRepresentation($command);
        } else {
            return $this->dispatcher->dispatchNow($command, $handler);
        }
    }

    /**
     * Dispatch a command to its appropriate handler behind a queue.
     *
     * @param  mixed  $command
     * @return mixed
     */
    public function dispatchToQueue($command)
    {
        if ($this->shouldFakeJob($command)) {
            $this->commands[get_class($command)][] = $this->getCommandRepresentation($command);
        } else {
            return $this->dispatcher->dispatchToQueue($command);
        }
    }

    /**
     * Dispatch a command to its appropriate handler.
     *
     * @param  mixed  $command
     * @return mixed
     */
    public function dispatchAfterResponse($command)
    {
        if ($this->shouldFakeJob($command)) {
            $this->commandsAfterResponse[get_class($command)][] = $this->getCommandRepresentation($command);
        } else {
            return $this->dispatcher->dispatch($command);
        }
    }

    /**
     * Create a new chain of queueable jobs.
     *
     * @param  \LaraGram\Support\Collection|array  $jobs
     * @return \LaraGram\Foundation\Bus\PendingChain
     */
    public function chain($jobs)
    {
        $jobs = Collection::wrap($jobs);
        $jobs = ChainedBatch::prepareNestedBatches($jobs);

        return new PendingChainFake($this, $jobs->shift(), $jobs->toArray());
    }

    /**
     * Attempt to find the batch with the given ID.
     *
     * @param  string  $batchId
     * @return \LaraGram\Bus\Batch|null
     */
    public function findBatch(string $batchId)
    {
        return $this->batchRepository->find($batchId);
    }

    /**
     * Create a new batch of queueable jobs.
     *
     * @param  \LaraGram\Support\Collection|array  $jobs
     * @return \LaraGram\Bus\PendingBatch
     */
    public function batch($jobs)
    {
        return new PendingBatchFake($this, Collection::wrap($jobs));
    }

    /**
     * Dispatch an empty job batch for testing.
     *
     * @param  string  $name
     * @return \LaraGram\Bus\Batch
     */
    public function dispatchFakeBatch($name = '')
    {
        return $this->batch([])->name($name)->dispatch();
    }

    /**
     * Record the fake pending batch dispatch.
     *
     * @param  \LaraGram\Bus\PendingBatch  $pendingBatch
     * @return \LaraGram\Bus\Batch
     */
    public function recordPendingBatch(PendingBatch $pendingBatch)
    {
        $this->batches[] = $pendingBatch;

        return $this->batchRepository->store($pendingBatch);
    }

    /**
     * Determine if a command should be faked or actually dispatched.
     *
     * @param  mixed  $command
     * @return bool
     */
    protected function shouldFakeJob($command)
    {
        if ($this->shouldDispatchCommand($command)) {
            return false;
        }

        if (empty($this->jobsToFake)) {
            return true;
        }

        return (new Collection($this->jobsToFake))
            ->filter(function ($job) use ($command) {
                return $job instanceof Closure
                    ? $job($command)
                    : $job === get_class($command);
            })->isNotEmpty();
    }

    /**
     * Determine if a command should be dispatched or not.
     *
     * @param  mixed  $command
     * @return bool
     */
    protected function shouldDispatchCommand($command)
    {
        return (new Collection($this->jobsToDispatch))
            ->filter(function ($job) use ($command) {
                return $job instanceof Closure
                    ? $job($command)
                    : $job === get_class($command);
            })->isNotEmpty();
    }

    /**
     * Specify if commands should be serialized and restored when being batched.
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
     * Serialize and unserialize the command to simulate the queueing process.
     *
     * @param  mixed  $command
     * @return mixed
     */
    protected function serializeAndRestoreCommand($command)
    {
        return unserialize(serialize($command));
    }

    /**
     * Return the command representation that should be stored.
     *
     * @param  mixed  $command
     * @return mixed
     */
    protected function getCommandRepresentation($command)
    {
        return $this->serializeAndRestore ? $this->serializeAndRestoreCommand($command) : $command;
    }

    /**
     * Set the pipes commands should be piped through before dispatching.
     *
     * @param  array  $pipes
     * @return $this
     */
    public function pipeThrough(array $pipes)
    {
        $this->dispatcher->pipeThrough($pipes);

        return $this;
    }

    /**
     * Determine if the given command has a handler.
     *
     * @param  mixed  $command
     * @return bool
     */
    public function hasCommandHandler($command)
    {
        return $this->dispatcher->hasCommandHandler($command);
    }

    /**
     * Retrieve the handler for a command.
     *
     * @param  mixed  $command
     * @return mixed
     */
    public function getCommandHandler($command)
    {
        return $this->dispatcher->getCommandHandler($command);
    }

    /**
     * Map a command to a handler.
     *
     * @param  array  $map
     * @return $this
     */
    public function map(array $map)
    {
        $this->dispatcher->map($map);

        return $this;
    }

    /**
     * Get the batches that have been dispatched.
     *
     * @return array
     */
    public function dispatchedBatches()
    {
        return $this->batches;
    }
}
