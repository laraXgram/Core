<?php

namespace LaraGram\Request;

use LaraGram\Support\ServiceProvider;

class RequestServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('request', function () {
            return new Request();
        });
    }
}