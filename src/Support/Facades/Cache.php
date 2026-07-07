<?php

namespace LaraGram\Support\Facades;

/**
 * @method static \LaraGram\Contracts\Cache\Repository store(\UnitEnum|string|null $name = null)
 * @method static \LaraGram\Contracts\Cache\Repository driver(\UnitEnum|string|null $driver = null)
 * @method static \LaraGram\Contracts\Cache\Repository memo(\UnitEnum|string|null $driver = null)
 * @method static \LaraGram\Contracts\Cache\Repository resolve(string $name)
 * @method static \LaraGram\Cache\Repository build(array $config)
 * @method static \LaraGram\Cache\Repository repository(\LaraGram\Contracts\Cache\Store $store, array $config = [])
 * @method static void refreshEventDispatcher()
 * @method static string getDefaultDriver()
 * @method static void setDefaultDriver(\UnitEnum|string $name)
 * @method static \LaraGram\Cache\CacheManager forgetDriver(array|\UnitEnum|string|null $name = null)
 * @method static void purge(\UnitEnum|string|null $name = null)
 * @method static \LaraGram\Cache\CacheManager extend(string $driver, \Closure $callback)
 * @method static \LaraGram\Cache\CacheManager setApplication(\LaraGram\Contracts\Foundation\Application $app)
 * @method static void handleUnserializableClassUsing(callable|null $callback)
 * @method static bool has(\UnitEnum|array|string $key)
 * @method static bool missing(\UnitEnum|string $key)
 * @method static mixed get(\UnitEnum|array|string $key, mixed $default = null)
 * @method static array many(array $keys)
 * @method static iterable getMultiple(iterable $keys, mixed $default = null)
 * @method static mixed pull(\UnitEnum|array|string $key, mixed $default = null)
 * @method static string string(\UnitEnum|string $key, \Closure|string|null $default = null)
 * @method static int integer(\UnitEnum|string $key, \Closure|int|null $default = null)
 * @method static float float(\UnitEnum|string $key, \Closure|float|null $default = null)
 * @method static bool boolean(\UnitEnum|string $key, \Closure|bool|null $default = null)
 * @method static array array(\UnitEnum|string $key, \Closure|array|null $default = null)
 * @method static bool put(\UnitEnum|array|string $key, mixed $value, \DateTimeInterface|\DateInterval|int|null $ttl = null)
 * @method static bool set(\UnitEnum|array|string $key, mixed $value, \DateTimeInterface|\DateInterval|int|null $ttl = null)
 * @method static bool putMany(array $values, \DateTimeInterface|\DateInterval|int|null $ttl = null)
 * @method static bool setMultiple(iterable $values, null|int|\DateInterval $ttl = null)
 * @method static bool add(\UnitEnum|array|string $key, mixed $value, \DateTimeInterface|\DateInterval|int|null $ttl = null)
 * @method static int|bool increment(\UnitEnum|string $key, mixed $value = 1)
 * @method static int|bool decrement(\UnitEnum|string $key, mixed $value = 1)
 * @method static bool forever(\UnitEnum|string $key, mixed $value)
 * @method static mixed remember(\UnitEnum|string $key, \Closure|\DateTimeInterface|\DateInterval|int|null $ttl, \Closure $callback)
 * @method static array rememberWithWarmth(\UnitEnum|string $key, \Closure|\DateTimeInterface|\DateInterval|int|null $ttl, \Closure $callback)
 * @method static mixed sear(\UnitEnum|string $key, \Closure $callback)
 * @method static mixed rememberForever(\UnitEnum|string $key, \Closure $callback)
 * @method static mixed flexible(\UnitEnum|string $key, array $ttl, callable $callback, array|null $lock = null, bool $alwaysDefer = false)
 * @method static bool touch(\UnitEnum|string $key, \DateTimeInterface|\DateInterval|int $ttl)
 * @method static mixed withoutOverlapping(\UnitEnum|string $key, callable $callback, int $lockFor = 0, int $waitFor = 10, string|null $owner = null)
 * @method static \LaraGram\Cache\Limiters\ConcurrencyLimiterBuilder funnel(\UnitEnum|string $name)
 * @method static bool forget(\UnitEnum|array|string $key)
 * @method static bool delete(\UnitEnum|array|string $key)
 * @method static bool deleteMultiple(iterable $keys)
 * @method static bool clear()
 * @method static bool flushLocks()
 * @method static \LaraGram\Cache\TaggedCache tags(mixed $names)
 * @method static string|null getName()
 * @method static bool supportsTags()
 * @method static bool supportsFlushingLocks()
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
