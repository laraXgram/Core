<?php

namespace LaraGram\Foundation\Configuration;

use LaraGram\Console\Kernel;
use LaraGram\Foundation\Application;
use LaraGram\Foundation\Bootstrap\RegisterProviders;
use LaraGram\Foundation\CoreCommand;
use LaraGram\Foundation\Support\Providers\EventServiceProvider as AppEventServiceProvider;


class ApplicationBuilder
{
    /**
     * The service provider that are marked for registration.
     *
     * @var array
     */
    protected array $pendingProviders = [];

    /**
     * Create a new application builder instance.
     */
    public function __construct(protected Application $app)
    {
    }

    /**
     * Register the standard kernel classes for the application.
     *
     * @return $this
     */
    public function withKernels()
    {
        $this->app->singleton(
            \LaraGram\Contracts\Console\Kernel::class,
            \LaraGram\Foundation\Console\Kernel::class,
        );

        return $this;
    }

    /**
     * Register additional service providers.
     *
     * @param array $providers
     * @param bool $withBootstrapProviders
     * @return $this
     */
    public function withProviders(array $providers = [], bool $withBootstrapProviders = true)
    {
        RegisterProviders::merge(
            $providers,
            $withBootstrapProviders
                ? $this->app->getBootstrapProvidersPath()
                : null
        );

        return $this;
    }

    /**
     * Register the core event service provider for the application.
     *
     * @param  array|bool  $discover
     * @return $this
     */
    public function withEvents(array|bool $discover = [])
    {
        if (is_array($discover) && count($discover) > 0) {
            AppEventServiceProvider::setEventDiscoveryPaths($discover);
        }

        if ($discover === false) {
            AppEventServiceProvider::disableEventDiscovery();
        }

        if (! isset($this->pendingProviders[AppEventServiceProvider::class])) {
            $this->app->booting(function () {
                $this->app->register(AppEventServiceProvider::class);
            });
        }

        $this->pendingProviders[AppEventServiceProvider::class] = true;

        return $this;
    }

    /**
     * Register additional Artisan commands with the application.
     *
     * @param  array  $commands
     * @return $this
     */
    public function withCommands(array $commands = [])
    {
        if (empty($commands)) {
            $commands = [$this->app->appPath('Console/Commands')];
        }

        $this->app->afterResolving(\LaraGram\Contracts\Console\Kernel::class, function ($kernel) use ($commands) {
            $existingCommands = [];
            $paths = [];

            foreach ($commands as $command) {
                if (class_exists($command)) {
                    $existingCommands[] = $command;
                } else {
                    $paths[] = $command;
                }
            }

            $validPaths = array_filter($paths, fn($path) => is_dir($path));

            $this->app->booted(static function () use ($kernel, $existingCommands, $validPaths) {
                $kernel->addCommands($existingCommands);
                $kernel->addCommandPaths($validPaths);
            });
        });

        return $this;
    }


    /**
     * Register an array of container bindings to be bound when the application is booting.
     *
     * @param array $bindings
     * @return $this
     */
    public function withBindings(array $bindings)
    {
        return $this->registered(function ($app) use ($bindings) {
            foreach ($bindings as $abstract => $concrete) {
                $app->bind($abstract, $concrete);
            }
        });
    }

    /**
     * Register an array of singleton container bindings to be bound when the application is booting.
     *
     * @param array $singletons
     * @return $this
     */
    public function withSingletons(array $singletons)
    {
        return $this->registered(function ($app) use ($singletons) {
            foreach ($singletons as $abstract => $concrete) {
                if (is_string($abstract)) {
                    $app->singleton($abstract, $concrete);
                } else {
                    $app->singleton($concrete);
                }
            }
        });
    }

    /**
     * Register and configure the application's exception handler.
     *
     * @param  callable|null  $using
     * @return $this
     */
    public function withExceptions()
    {
        $this->app->singleton(
            \LaraGram\Contracts\Debug\ExceptionHandler::class,
            \LaraGram\Foundation\Exceptions\Handler::class
        );

        return $this;
    }

    /**
     * Register a callback to be invoked when the application's service providers are registered.
     *
     * @param callable $callback
     * @return $this
     */
    public function registered(callable $callback)
    {
        $this->app->registered($callback);

        return $this;
    }

    /**
     * Register a callback to be invoked when the application is "booting".
     *
     * @param callable $callback
     * @return $this
     */
    public function booting(callable $callback)
    {
        $this->app->booting($callback);

        return $this;
    }

    /**
     * Register a callback to be invoked when the application is "booted".
     *
     * @param callable $callback
     * @return $this
     */
    public function booted(callable $callback)
    {
        $this->app->booted($callback);

        return $this;
    }

    /**
     * Get the application instance.
     *
     * @return Application
     */
    public function create()
    {
//        $this->app->handleRequests();

        return $this->app;
    }
}
