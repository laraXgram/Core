<?php

namespace LaraGram\Cache;

use LaraGram\Contracts\Support\DeferrableProvider;
use LaraGram\Support\ServiceProvider;

class CacheServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('cache', function ($app) {
            return new CacheManager($app);
        });

        $this->app->singleton('cache.store', function ($app) {
            return $app['cache']->driver();
        });

        $this->app->singleton('memcached.connector', function () {
            return new MemcachedConnector;
        });

        $this->app->singleton(RateLimiter::class, function ($app) {
            return new RateLimiter($app->make('cache')->driver(
                $app['config']->get('cache.limiter')
            ));
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [
            'cache', 'cache.store', 'memcached.connector', RateLimiter::class,
        ];
    }
}
