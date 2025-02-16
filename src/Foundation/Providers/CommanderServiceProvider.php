<?php

namespace LaraGram\Foundation\Providers;

use LaraGram\Console\Signals;
use LaraGram\Container\Container;
use LaraGram\Contracts\Support\DeferrableProvider;
use LaraGram\Database\Console\DbCommand;
use LaraGram\Database\Console\DumpCommand;
use LaraGram\Database\Console\Factories\FactoryMakeCommand;
use LaraGram\Database\Console\MonitorCommand;
use LaraGram\Database\Console\PruneCommand;
use LaraGram\Database\Console\Seeds\SeedCommand;
use LaraGram\Database\Console\Seeds\SeederMakeCommand;
use LaraGram\Database\Console\ShowCommand;
use LaraGram\Database\Console\ShowModelCommand;
use LaraGram\Database\Console\TableCommand;
use LaraGram\Database\Console\WipeCommand;
use LaraGram\Foundation\Console\CastMakeCommand;
use LaraGram\Foundation\Console\ClassMakeCommand;
use LaraGram\Foundation\Console\ConfigCacheCommand;
use LaraGram\Foundation\Console\ConfigClearCommand;
use LaraGram\Foundation\Console\ConfigPublishCommand;
use LaraGram\Foundation\Console\ConfigShowCommand;
use LaraGram\Foundation\Console\ConsoleMakeCommand;
use LaraGram\Foundation\Console\ControllerMakeCommand;
use LaraGram\Foundation\Console\ConversationMakeCommand;
use LaraGram\Foundation\Console\EnumMakeCommand;
use LaraGram\Foundation\Console\EventCacheCommand;
use LaraGram\Foundation\Console\EventClearCommand;
use LaraGram\Foundation\Console\InterfaceMakeCommand;
use LaraGram\Foundation\Console\ModelMakeCommand;
use LaraGram\Foundation\Console\ObserverMakeCommand;
use LaraGram\Foundation\Console\OptimizeClearCommand;
use LaraGram\Foundation\Console\OptimizeCommand;
use LaraGram\Foundation\Console\PackageDiscoverCommand;
use LaraGram\Foundation\Console\ProviderMakeCommand;
use LaraGram\Foundation\Console\ScopeMakeCommand;
use LaraGram\Foundation\Console\ServeCommand;
use LaraGram\Foundation\Console\StartApiServerCommand;
use LaraGram\Foundation\Console\StubPublishCommand;
use LaraGram\Foundation\Console\SwooleInstallCommand;
use LaraGram\Foundation\Console\TraitMakeCommand;
use LaraGram\Foundation\Console\VendorPublishCommand;
use LaraGram\Support\ServiceProvider;

class CommanderServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $commandsWithDependencied = [
//        GenerateAppCommand::class      => ['files'],
        ConfigCacheCommand::class      => ['files'],
        ConfigClearCommand::class      => ['files'],
        EventCacheCommand::class       => ['files'],
        EventClearCommand::class       => ['files'],
        OptimizeCommand::class         => ['files'],
        OptimizeClearCommand::class    => ['files'],
        StartApiServerCommand::class   => [],
        ServeCommand::class            => [],
        FactoryMakeCommand::class      => ['files'],
        SeedCommand::class             => ['db'],
        SeederMakeCommand::class       => ['files'],
        CastMakeCommand::class         => ['files'],
        ClassMakeCommand::class        => ['files'],
        ConsoleMakeCommand::class      => ['files'],
        ControllerMakeCommand::class   => ['files'],
        EnumMakeCommand::class         => ['files'],
        InterfaceMakeCommand::class    => ['files'],
        ModelMakeCommand::class        => ['files'],
        ObserverMakeCommand::class     => ['files'],
        ProviderMakeCommand::class     => ['files'],
        ScopeMakeCommand::class        => ['files'],
        TraitMakeCommand::class        => ['files'],
        ConversationMakeCommand::class => ['files'],
        VendorPublishCommand::class    => ['files'],
        ConfigPublishCommand::class    => [],
    ];

    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $commands = [
        'PruneCommand' => PruneCommand::class,
        'ShowCommand' => ShowCommand::class,
        'ShowModelCommand' => ShowModelCommand::class,
        'WipeCommand' => WipeCommand::class,
        'MonitorCommand' => MonitorCommand::class,
        'DumpCommand' => DumpCommand::class,
        'DbCommand' => DbCommand::class,
        'PackageDiscoverCommand' => PackageDiscoverCommand::class,
        'ConfigShowCommand' => ConfigShowCommand::class,
    ];

    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $devCommands = [
        'TableCommand' => TableCommand::class,
        'StubPublishCommand' => StubPublishCommand::class,
        'SwooleInstallCommand' => SwooleInstallCommand::class,
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
