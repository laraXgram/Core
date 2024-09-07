<?php

namespace LaraGram\Cache;

use LaraGram\Cache\Driver\APCuCacheDriver;
use LaraGram\Cache\Driver\DatabaseCacheDriver;
use LaraGram\Cache\Driver\FileCacheDriver;
use LaraGram\Cache\Driver\RedisCacheDriver;
use LaraGram\Contracts\CacheDriver;
use LaraGram\Support\Trait\Macroable;

class CacheManager
{
    use Macroable;

    protected CacheDriver $driver;

    public function __construct(CacheDriver $driver)
    {
        $this->driver = $driver;
    }

    public function driver(string|CacheDriver $driver): static
    {
        if (is_string($driver)) {
            $this->driver = match ($driver) {
                'file' => new FileCacheDriver(config()->get('cache.file.path')),
                'database' => new DatabaseCacheDriver(),
                'redis' => new RedisCacheDriver(app('redis.connection')),
                'apcu' => new APCuCacheDriver(),
                default => throw new \InvalidArgumentException("Invalid cache driver: {$driver}")
            };
        } else {
            $this->driver = $driver;
        }

        return $this;
    }

    public function get($key)
    {
        try {
            return $this->driver->get($key);
        } catch (\Exception $e) {
            file_put_contents('database.log', $e->getMessage());
            return false;
        }
    }

    public function set($key, $value, $ttl = 3600)
    {
        try {
            return $this->driver->set($key, $value, $ttl);
        } catch (\Exception $e) {
            file_put_contents('database.log', $e->getMessage());
            return false;
        }
    }

    public function forgot($key)
    {
        try {
            return $this->driver->forgot($key);
        } catch (\Exception $e) {
            file_put_contents('database.log', $e->getMessage());
            return false;
        }
    }

    public function clear()
    {
        try {
            return $this->driver->clear();
        } catch (\Exception $e) {
            file_put_contents('database.log', $e->getMessage());
            return false;
        }
    }
}