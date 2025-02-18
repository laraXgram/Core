<?php

namespace LaraGram\Support\Facades;

/**
 * @method static \LaraGram\Contracts\Cache\Repository store(string|null $name = null)
 * @method static \LaraGram\Contracts\Cache\Repository driver(string|null $driver = null)
 * @method static \LaraGram\Contracts\Cache\Repository resolve(string $name)
 * @method static \LaraGram\Cache\Repository build(array $config)
 * @method static \LaraGram\Cache\Repository repository(\LaraGram\Contracts\Cache\Store $store, array $config = [])
 * @method static void refreshEventDispatcher()
 * @method static string getDefaultDriver()
 * @method static void setDefaultDriver(string $name)
 * @method static \LaraGram\Cache\CacheManager forgetDriver(array|string|null $name = null)
 * @method static void purge(string|null $name = null)
 * @method static \LaraGram\Cache\CacheManager extend(string $driver, \Closure $callback)
 * @method static \LaraGram\Cache\CacheManager setApplication(\LaraGram\Contracts\Foundation\Application $app)
 * @method static bool has(array|string $key)
 * @method static bool missing(string $key)
 * @method static mixed get(array|string $key, mixed $default = null)
 * @method static array many(array $keys)
 * @method static iterable getMultiple(iterable $keys, mixed $default = null)
 * @method static mixed pull(array|string $key, mixed $default = null)
 * @method static bool put(array|string $key, mixed $value, \DateTimeInterface|\DateInterval|int|null $ttl = null)
 * @method static bool set(string $key, mixed $value, null|int|\DateInterval $ttl = null)
 * @method static bool putMany(array $values, \DateTimeInterface|\DateInterval|int|null $ttl = null)
 * @method static bool setMultiple(iterable $values, null|int|\DateInterval $ttl = null)
 * @method static bool add(string $key, mixed $value, \DateTimeInterface|\DateInterval|int|null $ttl = null)
 * @method static int|bool increment(string $key, mixed $value = 1)
 * @method static int|bool decrement(string $key, mixed $value = 1)
 * @method static bool forever(string $key, mixed $value)
 * @method static mixed remember(string $key, \Closure|\DateTimeInterface|\DateInterval|int|null $ttl, \Closure $callback)
 * @method static mixed sear(string $key, \Closure $callback)
 * @method static mixed rememberForever(string $key, \Closure $callback)
 * @method static mixed flexible(string $key, array $ttl, callable $callback, array|null $lock = null)
 * @method static bool forget(string $key)
 * @method static bool delete(string $key)
 * @method static bool deleteMultiple(iterable $keys)
 * @method static bool clear()
 * @method static \LaraGram\Cache\TaggedCache tags(array|mixed $names)
 * @method static bool supportsTags()
 * @method static int|null getDefaultCacheTime()
 * @method static \LaraGram\Cache\Repository setDefaultCacheTime(int|null $seconds)
 * @method static \LaraGram\Contracts\Cache\Store getStore()
 * @method static \LaraGram\Cache\Repository setStore(\LaraGram\Contracts\Cache\Store $store)
 * @method static \LaraGram\Contracts\Events\Dispatcher|null getEventDispatcher()
 * @method static void setEventDispatcher(\LaraGram\Contracts\Events\Dispatcher $events)
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 * @method static mixed macroCall(string $method, array $parameters)
 * @method static bool flush()
 * @method static string getPrefix()
 * @method static \LaraGram\Contracts\Cache\Lock lock(string $name, int $seconds = 0, string|null $owner = null)
 * @method static \LaraGram\Contracts\Cache\Lock restoreLock(string $name, string $owner)
 *
 * @see \LaraGram\Cache\CacheManager
 * @see \LaraGram\Cache\Repository
 */
class Cache extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'cache';
    }
}
