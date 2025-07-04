<?php

namespace LaraGram\Foundation\Support\Providers;

use Closure;
use LaraGram\Contracts\Listening\PatternGenerator;
use LaraGram\Listening\Listener;
use LaraGram\Support\ServiceProvider;
use LaraGram\Support\Traits\ForwardsCalls;

/**
 * @mixin \LaraGram\Listening\Listener
 */
class ListenServiceProvider extends ServiceProvider
{
    use ForwardsCalls;

    /**
     * The controller namespace for the application.
     *
     * @var string|null
     */
    protected $namespace;

    /**
     * The callback that should be used to load the application's listens.
     *
     * @var \Closure|null
     */
    protected $loadListensUsing;

    /**
     * The global callback that should be used to load the application's listens.
     *
     * @var \Closure|null
     */
    protected static $alwaysLoadListensUsing;

    /**
     * The callback that should be used to load the application's cached listens.
     *
     * @var \Closure|null
     */
    protected static $alwaysLoadCachedListensUsing;

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->booted(function () {
            $this->setRootControllerNamespace();

            if ($this->listensAreCached()) {
                $this->loadCachedListens();
            } else {
                $this->loadListens();

                $this->app->booted(function () {
                    $this->app['listener']->getListens()->refreshNameLookups();
                    $this->app['listener']->getListens()->refreshActionLookups();
                });
            }
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the callback that will be used to load the application's listens.
     *
     * @param  \Closure  $listensCallback
     * @return $this
     */
    protected function listens(Closure $listensCallback)
    {
        $this->loadListensUsing = $listensCallback;

        return $this;
    }

    /**
     * Register the callback that will be used to load the application's listens.
     *
     * @param  \Closure|null  $listensCallback
     * @return void
     */
    public static function loadListensUsing(?Closure $listensCallback)
    {
        self::$alwaysLoadListensUsing = $listensCallback;
    }

    /**
     * Register the callback that will be used to load the application's cached listens.
     *
     * @param  \Closure|null  $listensCallback
     * @return void
     */
    public static function loadCachedListensUsing(?Closure $listensCallback)
    {
        self::$alwaysLoadCachedListensUsing = $listensCallback;
    }

    /**
     * Set the root controller namespace for the application.
     *
     * @return void
     */
    protected function setRootControllerNamespace()
    {
        if (! is_null($this->namespace)) {
            $this->app[PatternGenerator::class]->setRootControllerNamespace($this->namespace);
        }
    }

    /**
     * Determine if the application listens are cached.
     *
     * @return bool
     */
    protected function listensAreCached()
    {
        return $this->app->listensAreCached();
    }

    /**
     * Load the cached listens for the application.
     *
     * @return void
     */
    protected function loadCachedListens()
    {
        if (! is_null(self::$alwaysLoadCachedListensUsing)) {
            $this->app->call(self::$alwaysLoadCachedListensUsing);

            return;
        }

        $this->app->booted(function () {
            require $this->app->getCachedListensPath();
        });
    }

    /**
     * Load the application listens.
     *
     * @return void
     */
    protected function loadListens()
    {
        if (! is_null(self::$alwaysLoadListensUsing)) {
            $this->app->call(self::$alwaysLoadListensUsing);
        }

        if (! is_null($this->loadListensUsing)) {
            $this->app->call($this->loadListensUsing);
        } elseif (method_exists($this, 'map')) {
            $this->app->call([$this, 'map']);
        }
    }

    /**
     * Pass dynamic methods onto the Listener instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->forwardCallTo(
            $this->app->make(Listener::class), $method, $parameters
        );
    }
}
