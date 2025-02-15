<?php

namespace LaraGram\Foundation\Providers;

use LaraGram\Console\Signals;
use LaraGram\Contracts\Support\DeferrableProvider;
use LaraGram\Foundation\Console\ConfigCacheCommand;
use LaraGram\Foundation\Console\ConfigClearCommand;
use LaraGram\Foundation\Console\EventCacheCommand;
use LaraGram\Foundation\Console\EventClearCommand;
use LaraGram\Foundation\Console\OptimizeClearCommand;
use LaraGram\Foundation\Console\OptimizeCommand;
use LaraGram\Foundation\Console\ServeCommand;
use LaraGram\Foundation\Console\StartApiServerCommand;
use LaraGram\Support\ServiceProvider;

class CommanderServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $commandsWithDependencied = [
//        GenerateAppCommand::class    => ['files'],
        ConfigCacheCommand::class    => ['files'],
        ConfigClearCommand::class    => ['files'],
        EventCacheCommand::class     => ['files'],
        EventClearCommand::class     => ['files'],
        OptimizeCommand::class       => ['files'],
        OptimizeClearCommand::class  => ['files'],
        StartApiServerCommand::class => [],
        ServeCommand::class          => [],
    ];

    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $commands = [

    ];

    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $devCommands = [

    ];

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerCommandsWithDependencies();

        $this->registerCommands(array_merge(
            $this->commands,
            $this->devCommands
        ));

        Signals::resolveAvailabilityUsing(function () {
            return $this->app->runningInConsole()
                && extension_loaded('pcntl');
        });
    }

    /**
     * Register the given commands.
     *
     * @param  array  $commands
     * @return void
     */
    protected function registerCommands(array $commands)
    {
        foreach ($commands as $commandName => $command) {
            $method = "register{$commandName}Command";

            if (method_exists($this, $method)) {
                $this->{$method}();
            } else {
                $this->app->singleton($command);
            }
        }

        $this->commands(array_values($commands));
    }

    /**
     * Register the given commands.
     *
     * @return void
     */
    protected function registerCommandsWithDependencies()
    {
        foreach ($this->commandsWithDependencied as $class => $dependencies) {
            $this->app->singleton($class, fn ($app) => $this->resolveDependencies($app, $dependencies, $class));
        }

        $this->commands(array_keys($this->commandsWithDependencied));
    }

    /**
     * Resolve and inject dependencies for a given command class.
     *
     * @param  \LaraGram\Contracts\Container\Container  $app
     * @param  array  $dependencies
     * @param  string  $class
     * @return object
     */
    protected function resolveDependencies($app, $dependencies, $class)
    {
        $resolvedDependencies = [];
        foreach ($dependencies as $dep) {
            $resolvedDependencies[] = $app[$dep];
        }

        return new $class(...$resolvedDependencies);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return array_merge(
            array_values($this->commands),
            array_values($this->devCommands),
            array_keys($this->commandsWithDependencied)
        );
    }
}
