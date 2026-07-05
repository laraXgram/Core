<?php

namespace LaraGram\Request\AntiFlood;

use LaraGram\Support\ServiceProvider;

class AntiFloodServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('antiflood', function ($app) {
            return new AntiFlood($app);
        });

        $this->app->alias('antiflood', AntiFlood::class);
    }
}
