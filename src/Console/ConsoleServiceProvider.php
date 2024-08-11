<?php

namespace LaraGram\Console;

use LaraGram\Support\ServiceProvider;

class ConsoleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('console', function () {
            return new Console();
        });

        $this->app->singleton('console.output', function () {
            return new Output();
        });

        $this->app->singleton('console.commands', function () {
           return [];
        });
    }
}