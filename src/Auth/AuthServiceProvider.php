<?php

namespace LaraGram\Auth;

use LaraGram\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
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