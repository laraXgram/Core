<?php

namespace LaraGram\Listening;

use LaraGram\Contracts\Listening\ResponseFactory as ResponseFactoryContract;
use LaraGram\Contracts\Listening\UrlGenerator as UrlGeneratorContract;
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
//        $this->registerUrlGenerator();
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

        $this->app->alias('listener', \LaraGram\Listening\Listener::class);
    }

    /**
     * Register the URL generator service.
     *
     * @return void
     */
    protected function registerUrlGenerator()
    {
        $this->app->singleton('url', function ($app) {
            $listens = $app['listener']->getListens();

            // The URL generator needs the listen collection that exists on the listener.
            // Keep in mind this is an object, so we're passing by references here
            // and all the registered listens will be available to the generator.
            $app->instance('listens', $listens);

            return new UrlGenerator(
                $listens, $app->rebinding(
                'request', $this->requestRebinder()
            ), $app['config']['app.asset_url']
            );
        });

        $this->app->extend('url', function (UrlGeneratorContract $url, $app) {
            $url->setKeyResolver(function () {
                $config = $this->app->make('config');

                return [$config->get('app.key'), ...($config->get('app.previous_keys') ?? [])];
            });

            // If the listen collection is "rebound", for example, when the listens stay
            // cached for the application, we will need to rebind the listens on the
            // URL generator instance so it has the latest version of the listens.
            $app->rebinding('listens', function ($app, $listens) {
                $app['url']->setListens($listens);
            });

            return $url;
        });
    }

    /**
     * Get the URL generator request rebinder.
     *
     * @return \Closure
     */
    protected function requestRebinder()
    {
        return function ($app, $request) {
            $app['url']->setRequest($request);
        };
    }

    /**
     * Register the Redirector service.
     *
     * @return void
     */
    protected function registerRedirector()
    {
        $this->app->singleton('redirect', function ($app) {
            return new Redirector($app['url']);
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

//        $this->app->bind(
//            \LaraGram\Contracts\Listening\Registrar::class,
//            \LaraGram\Listening\Listener::class
//        );
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
