<?php

namespace LaraGram\Queue;

use Exception;
use LaraGram\Bus\Batchable;
use LaraGram\Bus\UniqueLock;
use LaraGram\Contracts\Bus\Dispatcher;
use LaraGram\Contracts\Cache\Factory as CacheFactory;
use LaraGram\Contracts\Cache\Repository as Cache;
use LaraGram\Container\Container;
use LaraGram\Contracts\Encryption\Encrypter;
use LaraGram\Contracts\Queue\Job;
use LaraGram\Contracts\Queue\ShouldBeUnique;
use LaraGram\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use LaraGram\Database\Eloquent\ModelNotFoundException;
use LaraGram\Log\Context\Repository as ContextRepository;
use LaraGram\Pipeline\Pipeline;
use LaraGram\Queue\Attributes\DeleteWhenMissingModels;
use ReflectionClass;
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
     * Create a new handler instance.
     *
     * @param  \LaraGram\Contracts\Bus\Dispatcher  $dispatcher
     * @param  \LaraGram\Contracts\Container\Container  $container
     * @return void
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
            $this->handleModelNotFound($job, $e);
            return;
        }

        $this->dispatchThroughMiddleware($job, $command);

        if (! $job->isReleased() && ! $command instanceof ShouldBeUniqueUntilProcessing) {
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

        return (new Pipeline($this->container))->send($command)
            ->through(array_merge(method_exists($command, 'middleware') ? $command->middleware() : [], $command->middleware ?? []))
            ->then(function ($command) use ($job) {
                if ($command instanceof ShouldBeUniqueUntilProcessing) {
                    $this->ensureUniqueJobLockIsReleased($command);
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
        if (in_array(InteractsWithQueue::class, class_uses_recursive($instance))) {
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

        if (! in_array(Batchable::class, $uses) ||
            ! in_array(InteractsWithQueue::class, $uses)) {
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
        if ($command instanceof ShouldBeUnique) {
            (new UniqueLock($this->container->make(Cache::class)))->release($command);
        }
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
        $class = $job->resolveName();

        try {
            $reflectionClass = new ReflectionClass($class);

            $shouldDelete = $reflectionClass->getDefaultProperties()['deleteWhenMissingModels']
                ?? count($reflectionClass->getAttributes(DeleteWhenMissingModels::class)) !== 0;
        } catch (Exception) {
            $shouldDelete = false;
        }

        $this->ensureUniqueJobLockIsReleasedViaContext();

        if ($shouldDelete) {
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
            $context->getHidden('laravel_unique_job_cache_store'),
            $context->getHidden('laravel_unique_job_key'),
        ];

        if ($store && $key) {
            $this->container->make(CacheFactory::class)
                ->store($store)
                ->lock($key)
                ->forceRelease();
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
     * @return void
     */
    public function failed(array $data, $e, string $uuid)
    {
        $command = $this->getCommand($data);

        if (! $command instanceof ShouldBeUniqueUntilProcessing) {
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
        if (! in_array(Batchable::class, class_uses_recursive($command))) {
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
}
