<?php

namespace LaraGram\Auth;

use LaraGram\Contracts\Support\DeferrableProvider;
use LaraGram\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->singleton('auth', function () {
            return new Auth();
        });

        $this->app->singleton('auth.level', function () {
            return new Level();
        });

        $this->app->singleton('auth.role', function () {
            return new Role();
        });
    }

    public function provides(): array
    {
        return ['auth', 'auth.level', 'auth.role'];
    }
}