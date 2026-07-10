<?php

namespace LaraGram\Support\Facades;

use LaraGram\Queue\Worker;

/**
 * @method static void before(mixed $callback)
 * @method static void after(mixed $callback)
 * @method static void exceptionOccurred(mixed $callback)
 * @method static void looping(mixed $callback)
 * @method static void failing(mixed $callback)
 * @method static void starting(mixed $callback)
 * @method static void stopping(mixed $callback)
 * @method static void route(array|string $class, \UnitEnum|string|null $queue = null, \UnitEnum|string|null $connection = null)
 * @method static bool connected(\UnitEnum|string|null $name = null)
 * @method static \LaraGram\Contracts\Queue\Queue connection(\UnitEnum|string|null $name = null)
 * @method static void pause(string $connection, string $queue)
 * @method static void pauseFor(string $connection, string $queue, \DateTimeInterface|\DateInterval|int $ttl)
 * @method static void resume(string $connection, string $queue)
 * @method static bool isPaused(string $connection, string $queue)
 * @method static array getPausedQueues(string $connection, array $queues)
 * @method static void withoutInterruptionPolling()
 * @method static void extend(string $driver, \Closure $resolver)
 * @method static void addConnector(string $driver, \Closure $resolver)
 * @method static string getDefaultDriver()
 * @method static void setDefaultDriver(\UnitEnum|string $name)
 * @method static string getName(string|null $connection = null)
 * @method static \LaraGram\Contracts\Foundation\Application getApplication()
 * @method static \LaraGram\Queue\QueueManager setApplication(\LaraGram\Contracts\Foundation\Application $app)
 * @method static string|null resolveConnectionFromQueueRoute(object $queueable)
 * @method static string|null resolveQueueFromQueueRoute(object $queueable)
 * @method static int size(string|null $queue = null)
 * @method static int pendingSize(string|null $queue = null)
 * @method static int delayedSize(string|null $queue = null)
 * @method static int reservedSize(string|null $queue = null)
 * @method static int|null creationTimeOfOldestPendingJob(string|null $queue = null)
 * @method static mixed push(string|object $job, mixed $data = '', string|null $queue = null)
 * @method static mixed pushOn(string $queue, string|object $job, mixed $data = '')
 * @method static mixed pushRaw(string $payload, string|null $queue = null, array $options = [])
 * @method static mixed later(\DateTimeInterface|\DateInterval|int $delay, string|object $job, mixed $data = '', string|null $queue = null)
 * @method static mixed laterOn(string $queue, \DateTimeInterface|\DateInterval|int $delay, string|object $job, mixed $data = '')
 * @method static mixed bulk(array $jobs, mixed $data = '', string|null $queue = null)
 * @method static \LaraGram\Contracts\Queue\Job|null pop(string|null $queue = null)
 * @method static string getConnectionName()
 * @method static \LaraGram\Contracts\Queue\Queue setConnectionName(string $name)
 * @method static mixed getJobTries(mixed $job)
 * @method static mixed getJobBackoff(mixed $job)
 * @method static mixed getJobExpiration(mixed $job)
 * @method static void createPayloadUsing(callable|null $callback)
 * @method static array getConfig()
 * @method static \LaraGram\Queue\Queue setConfig(array $config)
 * @method static \LaraGram\Container\Container getContainer()
 * @method static void setContainer(\LaraGram\Container\Container $container)
 * @method static \LaraGram\Support\Testing\Fakes\QueueFake except(array|string $jobsToBeQueued)
 * @method static \LaraGram\Support\Collection pushed(string $job, callable|null $callback = null)
 * @method static \LaraGram\Support\Collection pushedRaw(null|\Closure $callback = null)
 * @method static \LaraGram\Support\Collection listenersPushed(string $listenerClass, \Closure|null $callback = null)
 * @method static bool hasPushed(string $job)
 * @method static \LaraGram\Support\Collection pendingJobs(\UnitEnum|string|null $queue = null)
 * @method static \LaraGram\Support\Collection delayedJobs(\UnitEnum|string|null $queue = null)
 * @method static \LaraGram\Support\Collection reservedJobs(\UnitEnum|string|null $queue = null)
 * @method static \LaraGram\Support\Collection allPendingJobs()
 * @method static \LaraGram\Support\Collection allDelayedJobs()
 * @method static \LaraGram\Support\Collection allReservedJobs()
 * @method static bool shouldFakeJob(object $job)
 * @method static void reserve(\Closure|string|object $job, \UnitEnum|string|null $queue = null)
 * @method static array pushedJobs()
 * @method static array rawPushes()
 * @method static \LaraGram\Support\Testing\Fakes\QueueFake serializeAndRestore(bool $serializeAndRestore = true)
 * @method static void releaseUniqueJobLocks()
 * @method static void clearReserved()
 *
 * @see \LaraGram\Queue\QueueManager
 * @see \LaraGram\Queue\Queue
 * @see \LaraGram\Support\Testing\Fakes\QueueFake
 */
class Queue extends Facade
{
    /**
     * Register a callback to be executed to pick jobs.
     *
     * @param  string  $workerName
     * @param  callable  $callback
     * @return void
     */
    public static function popUsing($workerName, $callback)
    {
        Worker::popUsing($workerName, $callback);
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'queue';
    }
}
