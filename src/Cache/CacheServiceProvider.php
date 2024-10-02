<?php

namespace LaraGram\Cache;

use LaraGram\Support\ServiceProvider;
use LaraGram\Cache\Driver\FileCacheDriver;
use LaraGram\Cache\Driver\DatabaseCacheDriver;
use LaraGram\Cache\Driver\RedisCacheDriver;
use LaraGram\Cache\Driver\APCuCacheDriver;

class CacheServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton('cache.manager', function () {
            $driver = config('cache.default');

            $cacheDriver = match ($driver){
                'file' => new FileCacheDriver(config('cache.file.path')),
                'database' => new DatabaseCacheDriver(),
                'redis' => new RedisCacheDriver(app('redis.connection')),
                'apcu' => new APCuCacheDriver(),
                default => throw new \InvalidArgumentException("Invalid cache driver: {$driver}")
            };

            return new CacheManager($cacheDriver);
        });
    }
}
