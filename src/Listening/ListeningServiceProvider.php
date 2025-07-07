<?php

namespace LaraGram\Listening;

use LaraGram\Contracts\Listening\ResponseFactory as ResponseFactoryContract;
use LaraGram\Template\Factory as TemplateFactoryContract;
use LaraGram\Listening\Contracts\CallableDispatcher as CallableDispatcherContract;
use LaraGram\Listening\Contracts\ControllerDispatcher as ControllerDispatcherContract;
use LaraGram\Support\ServiceProvider;

class ListeningServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerListener();
//        $this->registerRedirector();
        $this->registerResponseFactory();
        $this->registerCallableDispatcher();
        $this->registerControllerDispatcher();
    }

    /**
     * Register the listener instance.
     *
     * @return void
     */
    protected function registerListener()
    {
        $this->app->singleton('listener', function ($app) {
            return new Listener($app['events'], $app);
        });
    }

    /**
     * Register the Redirector service.
     *
     * @return void
     */
    protected function registerRedirector()
    {
        $this->app->singleton('redirect', function ($app) {
            return new Redirector();
        });
    }

    /**
     * Register the response factory implementation.
     *
     * @return void
     */
    protected function registerResponseFactory()
    {
        $this->app->singleton(ResponseFactoryContract::class, function ($app) {
//            return new ResponseFactory($app[TemplateFactoryContract::class], $app['redirect']);
            return new ResponseFactory($app[TemplateFactoryContract::class]);
        });
    }

    /**
     * Register the callable dispatcher.
     *
     * @return void
     */
    protected function registerCallableDispatcher()
    {
        $this->app->singleton(CallableDispatcherContract::class, function ($app) {
            return new CallableDispatcher($app);
        });
    }

    /**
     * Register the controller dispatcher.
     *
     * @return void
     */
    protected function registerControllerDispatcher()
    {
        $this->app->singleton(ControllerDispatcherContract::class, function ($app) {
            return new ControllerDispatcher($app);
        });
    }
}
