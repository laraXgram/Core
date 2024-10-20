<?php

namespace LaraGram\Foundation\Configuration;

use LaraGram\Console\Kernel;
use LaraGram\Foundation\Application;
use LaraGram\Foundation\Bootstrap\RegisterProviders;
use LaraGram\Foundation\CoreCommand;

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

        $this->app->bootstrap();

        return $this;
    }

    /**
     * Register the standard kernel classes for the application.
     *
     * @return $this
     */
    public function withKernels()
    {
        $this->app->singleton(Kernel::class);
        $this->app->alias(Kernel::class, 'kernel');

        return $this;
    }

    /**
     * Register additional LaraGram commands with the application.
     *
     * @param array $commands
     * @return $this
     */
    public function withCommands(array $commands = [])
    {
        $this->app->singleton(CoreCommand::class);
        $this->app->alias(CoreCommand::class, 'kernel.core_command');

        if (empty($commands)) {
            $commands = array_merge($this->app['kernel.core_command']->getCoreCommands(), config('app.commands'));
        }

        $kernel = $this->app['kernel'];
        $commandClasses = [];

        foreach ($commands as $command) {
            if (class_exists($command)) {
                $commandClasses[] = $command;
            }
        }

        $kernel->addCommands($commandClasses);
        $kernel->run();

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
        if (config('database.database.power') == 'on') {
            $this->app->registerEloquent();
        }

        $this->app->handleRequests();

        return $this->app;
    }
}
