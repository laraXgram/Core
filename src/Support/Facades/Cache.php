<?php

namespace LaraGram\Support\Facades;

use LaraGram\Cache\CacheManager;
use LaraGram\Contracts\Cache\CacheDriver;

/**
 * @method static mixed get(string $key)
 * @method static void set(string $key, mixed $value, int $ttl = 3600)
 * @method static bool has(string $key)
 * @method static bool hasNot(string $key)
 * @method static mixed pull(string $key)
 * @method static void forgot(string $key)
 * @method static void clear()
 * @method static CacheManager driver(string|CacheDriver $driver)
 * @method static void macro(string $name, callable $macro)
 * @method static bool hasMacro(string $name)
 */
class Cache extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'cache.manager';
    }
}