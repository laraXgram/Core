<?php

namespace LaraGram\Listening;

use LaraGram\Contracts\Listening\ResponseFactory as ResponseFactoryContract;
use LaraGram\Contracts\Listening\PathGenerator as PathGeneratorContract;
use LaraGram\Listening\Contracts\CallableDispatcher as CallableDispatcherContract;
use LaraGram\Listening\Contracts\ControllerDispatcher as ControllerDispatcherContract;
use LaraGram\Support\ServiceProvider;
use LaraGram\Template\Factory as TemplateFactoryContract;

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
        $this->registerPathGenerator();
        $this->registerRedirector();
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
     * Register the URL generator service.
     *
     * @return void
     */
    protected function registerPathGenerator()
    {
        $this->app->singleton('url', function ($app) {
            $listens = $app['listener']->getListens();

            $app->instance('listens', $listens);

            return new PathGenerator(
                $listens, $app->rebinding(
                'request', $this->requestRebinder()
            )
            );
        });

        $this->app->extend('url', function (PathGeneratorContract $url, $app) {
            $url->setCacheResolver(function () {
                return $this->app['cache'] ?? null;
            });

            $url->setKeyResolver(function () {
                $config = $this->app->make('config');

                return [$config->get('app.key'), ...($config->get('app.previous_keys') ?? [])];
            });

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
            $redirector = new Redirector($app['url']);

            if (isset($app['cache'])) {
                $redirector->setCache($app['cache']);
            }

            return $redirector;
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
            return new ResponseFactory($app[TemplateFactoryContract::class], $app['redirect']);
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
