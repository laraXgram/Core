<?php

namespace LaraGram\Redis;

use LaraGram\Support\ServiceProvider;

class RedisServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('redis.connection', function () {
            return new Connection();
        });
    }
}