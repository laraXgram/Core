<?php

namespace LaraGram\Redis;

use LaraGram\Contracts\Support\DeferrableProvider;
use LaraGram\Support\ServiceProvider;

class RedisServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->singleton('redis.connection', function () {
            return new Connection();
        });
    }

    public function provides(): array
    {
        return ['redis.connection'];
    }
}