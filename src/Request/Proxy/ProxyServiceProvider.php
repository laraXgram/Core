<?php

namespace LaraGram\Request\Proxy;

use LaraGram\Support\ServiceProvider;

class ProxyServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('proxy', function ($app) {
            return new ProxyManager($app);
        });

        $this->app->alias('proxy', ProxyManager::class);
    }
}
