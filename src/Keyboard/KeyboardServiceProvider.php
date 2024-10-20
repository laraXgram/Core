<?php

namespace LaraGram\Keyboard;

use LaraGram\Support\ServiceProvider;

class KeyboardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('keyboard', function () {
            return new Keyboard();
        });
    }

    public function provides(): array
    {
        return ['keyboard'];
    }
}