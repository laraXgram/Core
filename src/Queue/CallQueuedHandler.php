<?php

namespace LaraGram\Queue;

use Exception;
use LaraGram\Bus\Batchable;
use LaraGram\Bus\BatchRepository;
use LaraGram\Bus\DebounceLock;
use LaraGram\Bus\UniqueLock;
use LaraGram\Contracts\Bus\Dispatcher;
use LaraGram\Contracts\Cache\Factory as CacheFactory;
use LaraGram\Contracts\Cache\Repository as Cache;
use LaraGram\Contracts\Container\Container;
use LaraGram\Contracts\Encryption\Encrypter;
use LaraGram\Contracts\Queue\Job;
use LaraGram\Contracts\Queue\ShouldBeUnique;
use LaraGram\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use LaraGram\Database\Eloquent\ModelNotFoundException;
use LaraGram\Events\CallQueuedListener;
use LaraGram\Log\Context\Repository as ContextRepository;
use LaraGram\Pipeline\Pipeline;
use LaraGram\Queue\Events\JobDebounced;
use RuntimeException;

class CallQueuedHandler
{
    /**
     * The bus dispatcher implementation.
     *
     * @var \LaraGram\Contracts\Bus\Dispatcher
     */
    protected $dispatcher;

    /**
     * The container instance.
     *
     * @var \LaraGram\Contracts\Container\Container
     */
    protected $container;

    /**
     * The command currently being processed.
     *
     * @var mixed
     */
    protected $runningCommand;

    /**
     * Create a new handler instance.
     *
     * @param  \LaraGram\Contracts\Bus\Dispatcher  $dispatcher
     * @param  \LaraGram\Contracts\Container\Container  $container
     */
    public function __construct(Dispatcher $dispatcher, Container $container)
    {
        $this->container = $container;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Handle the queued job.
     *
     * @param  \LaraGram\Contracts\Queue\Job  $job
     * @param  array  $data
     * @return void
     */
    public function call(Job $job, array $data)
    {
        try {
            $command = $this->setJobInstanceIfNecessary(
                $job, $this->getCommand($data)
            );
        } catch (ModelNotFoundException $e) {
            return $this->handleModelNotFound($job, $e);
        }

        if ($this->commandShouldBeDebounced($command)) {
            return $this->deleteDebouncedJob($job, $command);
        }

        $this->runningCommand = $command;

        try {
            $this->dispatchThroughMiddleware($job, $command);
        } finally {
            $this->runningCommand = null;
        }

        if (! $job->isReleased() && ! $this->commandShouldBeUniqueUntilProcessing($command)) {
            $this->ensureUniqueJobLockIsReleased($command);
        }

        if (! $job->hasFailed() && ! $job->isReleased()) {
            $this->ensureNextJobInChainIsDispatched($command);
            $this->ensureSuccessfulBatchJobIsRecorded($command);
        }

        if (! $job->isDeletedOrReleased()) {
            $job->delete();
        }
    }

    /**
     * Get the command from the given payload.
     *
     * @param  array  $data
     * @return mixed
     *
     * @throws \RuntimeException
     */
    protected function getCommand(array $data)
    {
        if (str_starts_with($data['command'], 'O:')) {
            return unserialize($data['command']);
        }

        if ($this->container->bound(Encrypter::class)) {
            return unserialize($this->container[Encrypter::class]->decrypt($data['command']));
        }

        throw new RuntimeException('Unable to extract job payload.');
    }

    /**
     * Dispatch the given job / command through its specified middleware.
     *
     * @param  \LaraGram\Contracts\Queue\Job  $job
     * @param  mixed  $command
     * @return mixed
     */
    protected function dispatchThroughMiddleware(Job $job, $command)
    {
        if ($command instanceof \__PHP_Incomplete_Class) {
            throw new Exception('Job is incomplete class: '.json_encode($command));
        }

        $lockReleased = false;

        return (new Pipeline($this->container))->send($command)
            ->through(array_merge(method_exists($command, 'middleware') ? $command->middleware() : [], $command->middleware ?? []))
            ->finally(function ($command) use (&$lockReleased) {
                if (! $lockReleased && $this->commandShouldBeUniqueUntilProcessing($command) && ! $command->job->isReleased() && $command->job->attempts() <= 1) {
                    $this->ensureUniqueJobLockIsReleased($command);
                }
            })
            ->then(function ($command) use ($job, &$lockReleased) {
                if ($this->commandShouldBeUniqueUntilProcessing($command) && $job->attempts() <= 1) {
                    $this->ensureUniqueJobLockIsReleased($command);

                    $lockReleased = true;
                }

                return $this->dispatcher->dispatchNow(
                    $command, $this->resolveHandler($job, $command)
                );
            });
    }

    /**
     * Resolve the handler for the given command.
     *
     * @param  \LaraGram\Contracts\Queue\Job  $job
     * @param  mixed  $command
     * @return mixed
     */
    protected function resolveHandler($job, $command)
    {
        $handler = $this->dispatcher->getCommandHandler($command) ?: null;

        if ($handler) {
            $this->setJobInstanceIfNecessary($job, $handler);
        }

        return $handler;
    }

    /**
     * Set the job instance of the given class if necessary.
     *
     * @param  \LaraGram\Contracts\Queue\Job  $job
     * @param  mixed  $instance
     * @return mixed
     */
    protected function setJobInstanceIfNecessary(Job $job, $instance)
    {
        if (isset(class_uses_recursive($instance)[InteractsWithQueue::class])) {
            $instance->setJob($job);
        }

        return $instance;
    }

    /**
     * Ensure the next job in the chain is dispatched if applicable.
     *
     * @param  mixed  $command
     * @return void
     */
    protected function ensureNextJobInChainIsDispatched($command)
    {
        if (method_exists($command, 'dispatchNextJobInChain')) {
            $command->dispatchNextJobInChain();
        }
    }

    /**
     * Ensure the batch is notified of the successful job completion.
     *
     * @param  mixed  $command
     * @return void
     */
    protected function ensureSuccessfulBatchJobIsRecorded($command)
    {
        $uses = class_uses_recursive($command);

        if (! isset($uses[Batchable::class], $uses[InteractsWithQueue::class])) {
            return;
        }

        if ($batch = $command->batch()) {
            $batch->recordSuccessfulJob($command->job->uuid());
        }
    }

    /**
     * Ensure the lock for a unique job is released.
     *
     * @param  mixed  $command
     * @return void
     */
    protected function ensureUniqueJobLockIsReleased($command)
    {
        if ($this->commandShouldBeUnique($command)) {
            (new UniqueLock($this->container->make(Cache::class)))->release($command);
        }
    }

    /**
     * Determine if the debounced command was superseded by a newer dispatch.
     *
     * @param  mixed  $command
     * @return bool
     */
    protected function commandShouldBeDebounced($command)
    {
        $owner = $command->debounceOwner ?? '';

        if (empty($owner)) {
            return false;
        }

        $lock = new DebounceLock($this->container->make(Cache::class));

        $currentOwner = $lock->getCurrentOwner($command);

        // Fail-open: if the lock no longer exists (cache eviction, TTL expiry), let the job execute...
        if (is_null($currentOwner)) {
            return false;
        }

        return $currentOwner !== $owner;
    }

    /**
     * Handle a debounced (superseded) job by firing an event and deleting it.
     *
     * @param  \LaraGram\Contracts\Queue\Job  $job
     * @param  mixed  $command
     * @return void
     */
    protected function deleteDebouncedJob($job, $command)
    {
        if ($this->container->bound('events')) {
            $this->container->make('events')->dispatch(
                new JobDebounced($job->getConnectionName(), $job, $command)
            );
        }

        $job->delete();
    }

    /**
     * Determine if the given command should be unique.
     */
    protected function commandShouldBeUnique(mixed $command): bool
    {
        return $command instanceof ShouldBeUnique ||
            ($command instanceof CallQueuedListener && $command->shouldBeUnique());
    }

    /**
     * Determine if the given command should be unique until processing begins.
     */
    protected function commandShouldBeUniqueUntilProcessing(mixed $command): bool
    {
        return $command instanceof ShouldBeUniqueUntilProcessing ||
            ($command instanceof CallQueuedListener && $command->shouldBeUniqueUntilProcessing());
    }

    /**
     * Handle a model not found exception.
     *
     * @param  \LaraGram\Contracts\Queue\Job  $job
     * @param  \Throwable  $e
     * @return void
     */
    protected function handleModelNotFound(Job $job, $e)
    {
        $this->ensureUniqueJobLockIsReleasedViaContext();

        if ($job->payload()['deleteWhenMissingModels'] ?? false) {
            $this->ensureSuccessfulBatchJobIsRecordedForMissingModel($job, $job->resolveQueuedJobClass());

            return $job->delete();
        }

        return $job->fail($e);
    }

    /**
     * Ensure the lock for a unique job is released via context.
     *
     * This is required when we can't unserialize the job due to missing models.
     *
     * @return void
     */
    protected function ensureUniqueJobLockIsReleasedViaContext()
    {
        if (! $this->container->bound(ContextRepository::class) ||
            ! $this->container->bound(CacheFactory::class)) {
            return;
        }

        $context = $this->container->make(ContextRepository::class);

        [$store, $key] = [
            $context->getHidden('laragram_unique_job_cache_store'),
            $context->getHidden('laragram_unique_job_key'),
        ];

        if ($store && $key) {
            $this->container->make(CacheFactory::class)
                ->store($store)
                ->lock($key)
                ->forceRelease();
        }
    }

    /**
     * Record a potentially batched job as successful when deleted because models were missing.
     *
     * @param  \LaraGram\Contracts\Queue\Job  $job
     * @param  string  $class
     * @return void
     */
    protected function ensureSuccessfulBatchJobIsRecordedForMissingModel(Job $job, string $class)
    {
        if (! isset(class_uses_recursive($class)[Batchable::class])) {
            return;
        }

        if (! $this->container->bound(BatchRepository::class)) {
            return;
        }

        $batchId = $job->payload()['data']['batchId'] ?? null;

        if ((! is_string($batchId) || $batchId === '') ||
             ! is_string($job->uuid()) || $job->uuid() === '') {
            return;
        }

        if ($batch = $this->container->make(BatchRepository::class)->find($batchId)) {
            $batch->recordSuccessfulJob($job->uuid());
        }
    }

    /**
     * Call the failed method on the job instance.
     *
     * The exception that caused the failure will be passed.
     *
     * @param  array  $data
     * @param  \Throwable|null  $e
     * @param  string  $uuid
     * @param  \LaraGram\Contracts\Queue\Job|null  $job
     * @return void
     */
    public function failed(array $data, $e, string $uuid, ?Job $job = null)
    {
        $command = $this->getCommand($data);

        if (! is_null($job)) {
            $command = $this->setJobInstanceIfNecessary($job, $command);
        }

        if (! $this->commandShouldBeUniqueUntilProcessing($command)) {
            $this->ensureUniqueJobLockIsReleased($command);
        }

        if ($command instanceof \__PHP_Incomplete_Class) {
            return;
        }

        $this->ensureFailedBatchJobIsRecorded($uuid, $command, $e);
        $this->ensureChainCatchCallbacksAreInvoked($uuid, $command, $e);

        if (method_exists($command, 'failed')) {
            $command->failed($e);
        }
    }

    /**
     * Ensure the batch is notified of the failed job.
     *
     * @param  string  $uuid
     * @param  mixed  $command
     * @param  \Throwable  $e
     * @return void
     */
    protected function ensureFailedBatchJobIsRecorded(string $uuid, $command, $e)
    {
        if (! isset(class_uses_recursive($command)[Batchable::class])) {
            return;
        }

        if ($batch = $command->batch()) {
            $batch->recordFailedJob($uuid, $e);
        }
    }

    /**
     * Ensure the chained job catch callbacks are invoked.
     *
     * @param  string  $uuid
     * @param  mixed  $command
     * @param  \Throwable  $e
     * @return void
     */
    protected function ensureChainCatchCallbacksAreInvoked(string $uuid, $command, $e)
    {
        if (method_exists($command, 'invokeChainCatchCallbacks')) {
            $command->invokeChainCatchCallbacks($e);
        }
    }

    /**
     * Get the command currently being processed.
     *
     * @return mixed
     */
    public function getRunningCommand()
    {
        return $this->runningCommand;
    }
}
