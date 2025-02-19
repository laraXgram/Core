<?php

namespace LaraGram\Support\Facades;

use LaraGram\Concurrency\ConcurrencyManager;

/**
 * @method static mixed driver(string|null $name = null)
 * @method static \LaraGram\Concurrency\ProcessDriver createProcessDriver(array $config)
 * @method static \LaraGram\Concurrency\SyncDriver createSyncDriver(array $config)
 * @method static string getDefaultInstance()
 * @method static void setDefaultInstance(string $name)
 * @method static array getInstanceConfig(string $name)
 * @method static mixed instance(string|null $name = null)
 * @method static \LaraGram\Concurrency\ConcurrencyManager forgetInstance(array|string|null $name = null)
 * @method static void purge(string|null $name = null)
 * @method static \LaraGram\Concurrency\ConcurrencyManager extend(string $name, \Closure $callback)
 * @method static \LaraGram\Concurrency\ConcurrencyManager setApplication(\LaraGram\Contracts\Foundation\Application $app)
 * @method static array run(\Closure|array $tasks)
 * @method static \LaraGram\Support\Defer\DeferredCallback defer(\Closure|array $tasks)
 *
 * @see \LaraGram\Concurrency\ConcurrencyManager
 */
class Concurrency extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return ConcurrencyManager::class;
    }
}
