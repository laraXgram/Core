<?php

namespace LaraGram\Foundation\Providers;

use LaraGram\Cache\Console\CacheTableCommand;
use LaraGram\Cache\Console\ClearCommand as CacheClearCommand;
use LaraGram\Cache\Console\ForgetCommand as CacheForgetCommand;
use LaraGram\Cache\Console\PruneStaleTagsCommand;
use LaraGram\Concurrency\Console\InvokeSerializedClosureCommand;
use LaraGram\Console\Scheduling\ScheduleClearCacheCommand;
use LaraGram\Console\Scheduling\ScheduleFinishCommand;
use LaraGram\Console\Scheduling\ScheduleInterruptCommand;
use LaraGram\Console\Scheduling\ScheduleListCommand;
use LaraGram\Console\Scheduling\ScheduleRunCommand;
use LaraGram\Console\Scheduling\ScheduleTestCommand;
use LaraGram\Console\Scheduling\ScheduleWorkCommand;
use LaraGram\Console\Signals;
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
use LaraGram\Database\Console\TableCommand as DatabaseTableCommand;
use LaraGram\Database\Console\WipeCommand;
use LaraGram\Foundation\Console\AboutCommand;
use LaraGram\Foundation\Console\CastMakeCommand;
use LaraGram\Foundation\Console\ClassMakeCommand;
use LaraGram\Foundation\Console\ClearCompiledCommand;
use LaraGram\Foundation\Console\ConfigCacheCommand;
use LaraGram\Foundation\Console\ConfigClearCommand;
use LaraGram\Foundation\Console\ConfigPublishCommand;
use LaraGram\Foundation\Console\ConfigShowCommand;
use LaraGram\Foundation\Console\ConsoleMakeCommand;
use LaraGram\Foundation\Console\ConversationMakeCommand;
use LaraGram\Foundation\Console\EnumMakeCommand;
use LaraGram\Foundation\Console\EnvironmentCommand;
use LaraGram\Foundation\Console\EnvironmentDecryptCommand;
use LaraGram\Foundation\Console\EnvironmentEncryptCommand;
use LaraGram\Foundation\Console\EventCacheCommand;
use LaraGram\Foundation\Console\EventClearCommand;
use LaraGram\Foundation\Console\EventGenerateCommand;
use LaraGram\Foundation\Console\EventListCommand;
use LaraGram\Foundation\Console\EventMakeCommand;
use LaraGram\Foundation\Console\ExceptionMakeCommand;
use LaraGram\Foundation\Console\GenerateAppCommand;
use LaraGram\Foundation\Console\InterfaceMakeCommand;
use LaraGram\Foundation\Console\JobMakeCommand;
use LaraGram\Foundation\Console\JobMiddlewareMakeCommand;
use LaraGram\Foundation\Console\KeyGenerateCommand;
use LaraGram\Foundation\Console\ListenCacheCommand;
use LaraGram\Foundation\Console\ListenClearCommand;
use LaraGram\Foundation\Console\ListenerMakeCommand;
use LaraGram\Foundation\Console\ListenListCommand;
use LaraGram\Foundation\Console\ModelMakeCommand;
use LaraGram\Foundation\Console\ObserverMakeCommand;
use LaraGram\Foundation\Console\OptimizeClearCommand;
use LaraGram\Foundation\Console\OptimizeCommand;
use LaraGram\Foundation\Console\PackageDiscoverCommand;
use LaraGram\Foundation\Console\ProviderMakeCommand;
use LaraGram\Foundation\Console\ScopeMakeCommand;
use LaraGram\Foundation\Console\ServeCommand;
use LaraGram\Foundation\Console\StartApiServerCommand;
use LaraGram\Foundation\Console\StorageLinkCommand;
use LaraGram\Foundation\Console\StorageUnlinkCommand;
use LaraGram\Foundation\Console\StubPublishCommand;
use LaraGram\Foundation\Console\SwooleInstallCommand;
use LaraGram\Foundation\Console\TemplateCacheCommand;
use LaraGram\Foundation\Console\TemplateClearCommand;
use LaraGram\Foundation\Console\TemplateMakeCommand;
use LaraGram\Foundation\Console\TraitMakeCommand;
use LaraGram\Foundation\Console\VendorPublishCommand;
use LaraGram\Foundation\Console\WebhookDeleteCommand;
use LaraGram\Foundation\Console\WebhookDropCommand;
use LaraGram\Foundation\Console\WebhookInfoCommand;
use LaraGram\Foundation\Console\WebhookSetCommand;
use LaraGram\Listening\Console\ControllerMakeCommand;
use LaraGram\Listening\Console\MiddlewareMakeCommand;
use LaraGram\Queue\Console\BatchesTableCommand;
use LaraGram\Queue\Console\ClearCommand as QueueClearCommand;
use LaraGram\Queue\Console\FailedTableCommand;
use LaraGram\Queue\Console\FlushFailedCommand as FlushFailedQueueCommand;
use LaraGram\Queue\Console\ForgetFailedCommand as ForgetFailedQueueCommand;
use LaraGram\Queue\Console\ListenCommand as QueueListenCommand;
use LaraGram\Queue\Console\ListFailedCommand as ListFailedQueueCommand;
use LaraGram\Queue\Console\MonitorCommand as QueueMonitorCommand;
use LaraGram\Queue\Console\PruneBatchesCommand;
use LaraGram\Queue\Console\PruneFailedJobsCommand as QueuePruneFailedJobsCommand;
use LaraGram\Queue\Console\RestartCommand as QueueRestartCommand;
use LaraGram\Queue\Console\RetryBatchCommand as QueueRetryBatchCommand;
use LaraGram\Queue\Console\RetryCommand as QueueRetryCommand;
use LaraGram\Queue\Console\TableCommand;
use LaraGram\Queue\Console\WorkCommand as QueueWorkCommand;
use LaraGram\Support\ServiceProvider;

class CommanderServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $commands = [
        'ViewCache' => TemplateCacheCommand::class,
        'EnvironmentCommand' => EnvironmentCommand::class,
        'ClearCompiled' => ClearCompiledCommand::class,
        'DatabaseTableCommand' => DatabaseTableCommand::class,
        'EventList' => EventListCommand::class,
        'PruneCommand' => PruneCommand::class,
        'ShowCommand' => ShowCommand::class,
        'ShowModelCommand' => ShowModelCommand::class,
        'WipeCommand' => WipeCommand::class,
        'MonitorCommand' => MonitorCommand::class,
        'DumpCommand' => DumpCommand::class,
        'DbCommand' => DbCommand::class,
        'PackageDiscoverCommand' => PackageDiscoverCommand::class,
        'ConfigShowCommand' => ConfigShowCommand::class,
        'WebhookInfoCommand' => WebhookInfoCommand::class,
        'WebhookDeleteCommand' => WebhookDeleteCommand::class,
        'WebhookSetCommand' => WebhookSetCommand::class,
        'WebhookDropCommand' => WebhookDropCommand::class,
        'PruneStaleTagsCommand' => PruneStaleTagsCommand::class,
        'QueueClearCommand' => QueueClearCommand::class,
        'FlushFailedQueueCommand' => FlushFailedQueueCommand::class,
        'ListFailedQueueCommand' => ListFailedQueueCommand::class,
        'QueueRetryBatchCommand' => QueueRetryBatchCommand::class,
        'QueueRetryCommand' => QueueRetryCommand::class,
        'ScheduleClearCacheCommand' => ScheduleClearCacheCommand::class,
        'ScheduleFinishCommand' => ScheduleFinishCommand::class,
        'ScheduleInterruptCommand' => ScheduleInterruptCommand::class,
        'ScheduleListCommand' => ScheduleListCommand::class,
        'ScheduleRunCommand' => ScheduleRunCommand::class,
        'ScheduleTestCommand' => ScheduleTestCommand::class,
        'ScheduleWorkCommand' => ScheduleWorkCommand::class,
        'StorageLinkCommand' => StorageLinkCommand::class,
        'StorageUnlinkCommand' => StorageUnlinkCommand::class,
        'InvokeSerializedClosureCommand' => InvokeSerializedClosureCommand::class,
        'KeyGenerate' => KeyGenerateCommand::class,
    ];

    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $commandsWithDependencied = [
        TemplateClearCommand::class        => ['files'],
        TemplateMakeCommand::class         => ['files'],
        AboutCommand::class                => ['composer'],
        EnvironmentDecryptCommand::class   => ['files'],
        EnvironmentEncryptCommand::class   => ['files'],
        ExceptionMakeCommand::class        => ['files'],
        ListenListCommand::class           => ['listener'],
        GenerateAppCommand::class          => ['files'],
        ConfigCacheCommand::class          => ['files'],
        ConfigClearCommand::class          => ['files'],
        EventCacheCommand::class           => ['files'],
        EventClearCommand::class           => ['files'],
        EventMakeCommand::class            => ['files'],
        OptimizeCommand::class             => ['files'],
        OptimizeClearCommand::class        => ['files'],
        StartApiServerCommand::class       => [],
        ServeCommand::class                => [],
        FactoryMakeCommand::class          => ['files'],
        SeedCommand::class                 => ['db'],
        SeederMakeCommand::class           => ['files'],
        CastMakeCommand::class             => ['files'],
        ClassMakeCommand::class            => ['files'],
        ConsoleMakeCommand::class          => ['files'],
        ControllerMakeCommand::class       => ['files'],
        EnumMakeCommand::class             => ['files'],
        InterfaceMakeCommand::class        => ['files'],
        ModelMakeCommand::class            => ['files'],
        MiddlewareMakeCommand::class       => ['files'],
        ObserverMakeCommand::class         => ['files'],
        ProviderMakeCommand::class         => ['files'],
        ScopeMakeCommand::class            => ['files'],
        TraitMakeCommand::class            => ['files'],
        ConversationMakeCommand::class     => ['files'],
        VendorPublishCommand::class        => ['files'],
        ConfigPublishCommand::class        => [],
        CacheTableCommand::class           => ['files'],
        CacheClearCommand::class           => ['cache', 'files'],
        CacheForgetCommand::class          => ['cache'],
        ForgetFailedQueueCommand::class    => [],
        QueueListenCommand::class          => ['queue.listener'],
        QueueMonitorCommand::class         => ['queue', 'events'],
        PruneBatchesCommand::class         => [],
        QueuePruneFailedJobsCommand::class => [],
        QueueRestartCommand::class         => ['cache.store'],
        QueueWorkCommand::class            => ['queue.worker', 'cache.store'],
        JobMakeCommand::class              => ['files'],
        JobMiddlewareMakeCommand::class    => ['files'],
        ListenerMakeCommand::class         => ['files'],
        ListenCacheCommand::class          => ['files'],
        ListenClearCommand::class          => ['files'],
    ];

    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $devCommands = [
        'EventGenerate' => EventGenerateCommand::class,
        'StubPublishCommand' => StubPublishCommand::class,
        'SwooleInstallCommand' => SwooleInstallCommand::class,
        'BatchesTableCommand' => BatchesTableCommand::class,
        'FailedTableCommand' => FailedTableCommand::class,
        'TableCommand' => TableCommand::class,
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
