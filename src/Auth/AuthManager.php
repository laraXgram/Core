<?php

namespace LaraGram\Auth;

use Closure;

/**
 * @mixin \LaraGram\Contracts\Auth\Guard
 */
class AuthManager
{
    use CreatesUserProviders;

    /**
     * The application instance.
     *
     * @var \LaraGram\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * The currently authenticated user.
     *
     * @var \LaraGram\Contracts\Auth\Authenticatable|null
     */
    protected $user;

    /**
     * The user provider implementation.
     *
     * @var \LaraGram\Contracts\Auth\UserProvider
     */
    protected $provider;

    /**
     * The user resolver shared by various services.
     *
     * Determines the default user for Gate, Request, and the Authenticatable contract.
     *
     * @var \Closure
     */
    protected $userResolver;

    /**
     * Create a new Auth manager instance.
     *
     * @param  \LaraGram\Contracts\Foundation\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;

        $this->provider = $this->createUserProvider();

        $this->userResolver = fn () => $this->user();
    }

    /**
     * Get the currently authenticated user.
     *
     * @return \LaraGram\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {
        if (! is_null($this->user)) {
            return $this->user;
        }

        $this->user = $this->provider->retrieveByUserId(user()->id);

        return $this->user;
    }

    /**
     * Get the user resolver callback.
     *
     * @return \Closure
     */
    public function userResolver()
    {
        return $this->userResolver;
    }

    /**
     * Set the callback to be used to resolve users.
     *
     * @param  \Closure  $userResolver
     * @return $this
     */
    public function resolveUsersUsing(Closure $userResolver)
    {
        $this->userResolver = $userResolver;

        return $this;
    }

    /**
     * Register a custom provider creator Closure.
     *
     * @param  string  $name
     * @param  \Closure  $callback
     * @return $this
     */
    public function provider($name, Closure $callback)
    {
        $this->customProviderCreators[$name] = $callback;

        return $this;
    }

    /**
     * Set the application instance used by the manager.
     *
     * @param  \LaraGram\Contracts\Foundation\Application  $app
     * @return $this
     */
    public function setApplication($app)
    {
        $this->app = $app;

        return $this;
    }
}
