<?php

namespace LaraGram\Auth;

use LaraGram\Auth\Access\Gate;
use LaraGram\Contracts\Auth\Access\Gate as GateContract;
use LaraGram\Contracts\Auth\Authenticatable as AuthenticatableContract;
use LaraGram\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerAuthenticator();
        $this->registerUserResolver();
        $this->registerAccessGate();
        $this->registerRequestRebindHandler();
    }

    /**
     * Register the authenticator services.
     *
     * @return void
     */
    protected function registerAuthenticator()
    {
        $this->app->singleton('auth', fn ($app) => new AuthManager($app));
    }

    /**
     * Register a resolver for the authenticated user.
     *
     * @return void
     */
    protected function registerUserResolver()
    {
        $this->app->bind(AuthenticatableContract::class, fn ($app) => call_user_func($app['auth']->userResolver()));
    }

    /**
     * Register the access gate service.
     *
     * @return void
     */
    protected function registerAccessGate()
    {
        $this->app->singleton(GateContract::class, function ($app) {
            return new Gate($app, fn () => call_user_func($app['auth']->userResolver()));
        });
    }

    /**
     * Handle the re-binding of the request binding.
     *
     * @return void
     */
    protected function registerRequestRebindHandler()
    {
        $this->app->rebinding('request', function ($app, $request) {
            $request->setUserResolver(function () use ($app) {
                return call_user_func($app['auth']->userResolver());
            });
        });
    }
}
