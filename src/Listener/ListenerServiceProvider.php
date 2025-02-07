<?php

namespace LaraGram\Listener;

use LaraGram\Support\ServiceProvider;

class ListenerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('listener', function () {
            return new Listener(new Group());
        });
    }
}