<?php

namespace LaraGram\Keyboard;

use LaraGram\Contracts\Support\DeferrableProvider;
use LaraGram\Support\ServiceProvider;

class KeyboardServiceProvider extends ServiceProvider implements DeferrableProvider
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