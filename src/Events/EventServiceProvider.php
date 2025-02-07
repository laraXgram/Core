<?php

namespace LaraGram\Events;

use LaraGram\Contracts\Queue\Factory as QueueFactoryContract;
use LaraGram\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('events', function ($app) {
            return (new Dispatcher($app))->setQueueResolver(function () use ($app) {
                return $app->make(QueueFactoryContract::class);
            });
        });
    }
}
