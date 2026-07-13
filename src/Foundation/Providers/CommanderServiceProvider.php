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
use LaraGram\Console\Scheduling\SchedulePauseCommand;
use LaraGram\Console\Scheduling\ScheduleResumeCommand;
use LaraGram\Console\Scheduling\ScheduleRunCommand;
use LaraGram\Console\Scheduling\ScheduleTestCommand;
use LaraGram\Console\Scheduling\ScheduleWorkCommand;
use LaraGram\Console\Signals;
use LaraGram\Contracts\Support\DeferrableProvider;
use LaraGram\Database\Console\DbCommand;
use LaraGram\Database\Console\DumpCommand;
use LaraGram\Database\Console\Factories\FactoryMakeCommand;
use LaraGram\Database\Console\MonitorCommand as DatabaseMonitorCommand;
use LaraGram\Database\Console\PruneCommand;
use LaraGram\Database\Console\Seeds\SeedCommand;
use LaraGram\Database\Console\Seeds\SeederMakeCommand;
use LaraGram\Database\Console\ShowCommand;
use LaraGram\Database\Console\ShowModelCommand;
use LaraGram\Database\Console\TableCommand as DatabaseTableCommand;
use LaraGram\Database\Console\WipeCommand;
use LaraGram\Foundation\Console\AboutCommand;
use LaraGram\Foundation\Console\ApiInstallCommand;
use LaraGram\Foundation\Console\CastMakeCommand;
use LaraGram\Foundation\Console\ClassMakeCommand;
use LaraGram\Foundation\Console\ClearCompiledCommand;
use LaraGram\Foundation\Console\ComponentMakeCommand;
use LaraGram\Foundation\Console\ConfigCacheCommand;
use LaraGram\Foundation\Console\ConfigClearCommand;
use LaraGram\Foundation\Console\ConfigMakeCommand;
use LaraGram\Foundation\Console\ConfigPublishCommand;
use LaraGram\Foundation\Console\ConfigShowCommand;
use LaraGram\Foundation\Console\ConsoleMakeCommand;
use LaraGram\Foundation\Console\ConversationMakeCommand;
use LaraGram\Foundation\Console\DevCommand;
use LaraGram\Foundation\Console\DevListCommand;
use LaraGram\Foundation\Console\DownCommand;
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
use LaraGram\Foundation\Console\InterfaceMakeCommand;
use LaraGram\Foundation\Console\JobMakeCommand;
use LaraGram\Foundation\Console\JobMiddlewareMakeCommand;
use LaraGram\Foundation\Console\KeyGenerateCommand;
use LaraGram\Foundation\Console\LangPublishCommand;
use LaraGram\Foundation\Console\ListenCacheCommand;
use LaraGram\Foundation\Console\ListenClearCommand;
use LaraGram\Foundation\Console\ListenerMakeCommand;
use LaraGram\Foundation\Console\ListenListCommand;
use LaraGram\Foundation\Console\ModelMakeCommand;
use LaraGram\Foundation\Console\ObserverMakeCommand;
use LaraGram\Foundation\Console\OptimizeClearCommand;
use LaraGram\Foundation\Console\OptimizeCommand;
use LaraGram\Foundation\Console\PackageDiscoverCommand;
use LaraGram\Foundation\Console\PolicyMakeCommand;
use LaraGram\Foundation\Console\ProviderMakeCommand;
use LaraGram\Foundation\Console\ReloadCommand;
use LaraGram\Foundation\Console\RequestMakeCommand;
use LaraGram\Foundation\Console\ResourceMakeCommand;
use LaraGram\Foundation\Console\RouteCacheCommand;
use LaraGram\Foundation\Console\RouteClearCommand;
use LaraGram\Foundation\Console\RouteListCommand;
use LaraGram\Foundation\Console\RuleMakeCommand;
use LaraGram\Foundation\Console\ScopeMakeCommand;
use LaraGram\Foundation\Console\ServeCommand;
use LaraGram\Foundation\Console\StorageLinkCommand;
use LaraGram\Foundation\Console\StorageUnlinkCommand;
use LaraGram\Foundation\Console\StubPublishCommand;
use LaraGram\Foundation\Console\TemplateMakeCommand;
use LaraGram\Foundation\Console\TraitMakeCommand;
use LaraGram\Foundation\Console\UpCommand;
use LaraGram\Foundation\Console\VendorPublishCommand;
use LaraGram\Foundation\Console\ViewCacheCommand;
use LaraGram\Foundation\Console\ViewClearCommand;
use LaraGram\Foundation\Console\ViewMakeCommand;
use LaraGram\Foundation\Console\WebhookDeleteCommand;
use LaraGram\Foundation\Console\WebhookDropCommand;
use LaraGram\Foundation\Console\WebhookInfoCommand;
use LaraGram\Foundation\Console\WebhookSetCommand;
use LaraGram\Foundation\DevCommands;
use LaraGram\Queue\Console\BatchesTableCommand;
use LaraGram\Queue\Console\ClearCommand as QueueClearCommand;
use LaraGram\Queue\Console\FailedTableCommand;
use LaraGram\Queue\Console\FlushFailedCommand as FlushFailedQueueCommand;
use LaraGram\Queue\Console\ForgetFailedCommand as ForgetFailedQueueCommand;
use LaraGram\Queue\Console\ListenCommand as QueueListenCommand;
use LaraGram\Queue\Console\ListFailedCommand as ListFailedQueueCommand;
use LaraGram\Queue\Console\MonitorCommand as QueueMonitorCommand;
use LaraGram\Queue\Console\PauseCommand as QueuePauseCommand;
use LaraGram\Queue\Console\PruneBatchesCommand as QueuePruneBatchesCommand;
use LaraGram\Queue\Console\PruneFailedJobsCommand as QueuePruneFailedJobsCommand;
use LaraGram\Queue\Console\RestartCommand as QueueRestartCommand;
use LaraGram\Queue\Console\ResumeCommand as QueueResumeCommand;
use LaraGram\Queue\Console\RetryBatchCommand as QueueRetryBatchCommand;
use LaraGram\Queue\Console\RetryCommand as QueueRetryCommand;
use LaraGram\Queue\Console\TableCommand;
use LaraGram\Queue\Console\WorkCommand as QueueWorkCommand;
use LaraGram\Routing\Console\ControllerMakeCommand;
use LaraGram\Routing\Console\MiddlewareMakeCommand;
use LaraGram\Session\Console\SessionTableCommand;
use LaraGram\Support\ServiceProvider;

class CommanderServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $commands = [
        'About' => AboutCommand::class,
        'CacheClear' => CacheClearCommand::class,
        'CacheForget' => CacheForgetCommand::class,
        'ClearCompiled' => ClearCompiledCommand::class,
        'ConfigCache' => ConfigCacheCommand::class,
        'ConfigClear' => ConfigClearCommand::class,
        'ConfigShow' => ConfigShowCommand::class,
        'Db' => DbCommand::class,
        'DbMonitor' => DatabaseMonitorCommand::class,
        'DbPrune' => PruneCommand::class,
        'DbShow' => ShowCommand::class,
        'DbTable' => DatabaseTableCommand::class,
        'DbWipe' => WipeCommand::class,
        'Down' => DownCommand::class,
        'Environment' => EnvironmentCommand::class,
        'EnvironmentDecrypt' => EnvironmentDecryptCommand::class,
        'EnvironmentEncrypt' => EnvironmentEncryptCommand::class,
        'EventCache' => EventCacheCommand::class,
        'EventClear' => EventClearCommand::class,
        'EventList' => EventListCommand::class,
        'InvokeSerializedClosure' => InvokeSerializedClosureCommand::class,
        'KeyGenerate' => KeyGenerateCommand::class,
        'ListenCacheCommand' => ListenCacheCommand::class,
        'ListenClearCommand' => ListenClearCommand::class,
        'ListenListCommand' => ListenListCommand::class,
        'Optimize' => OptimizeCommand::class,
        'OptimizeClear' => OptimizeClearCommand::class,
        'PackageDiscover' => PackageDiscoverCommand::class,
        'PruneStaleTagsCommand' => PruneStaleTagsCommand::class,
        'QueueClear' => QueueClearCommand::class,
        'QueueFailed' => ListFailedQueueCommand::class,
        'QueueFlush' => FlushFailedQueueCommand::class,
        'QueueForget' => ForgetFailedQueueCommand::class,
        'QueueListen' => QueueListenCommand::class,
        'QueueMonitor' => QueueMonitorCommand::class,
        'QueuePause' => QueuePauseCommand::class,
        'QueuePruneBatches' => QueuePruneBatchesCommand::class,
        'QueuePruneFailedJobs' => QueuePruneFailedJobsCommand::class,
        'QueueRestart' => QueueRestartCommand::class,
        'QueueResume' => QueueResumeCommand::class,
        'QueueRetry' => QueueRetryCommand::class,
        'QueueRetryBatch' => QueueRetryBatchCommand::class,
        'QueueWork' => QueueWorkCommand::class,
        'Reload' => ReloadCommand::class,
        'RouteCache' => RouteCacheCommand::class,
        'RouteClear' => RouteClearCommand::class,
        'RouteList' => RouteListCommand::class,
        'SchemaDump' => DumpCommand::class,
        'Seed' => SeedCommand::class,
        'ScheduleFinish' => ScheduleFinishCommand::class,
        'ScheduleList' => ScheduleListCommand::class,
        'ScheduleRun' => ScheduleRunCommand::class,
        'ScheduleClearCache' => ScheduleClearCacheCommand::class,
        'ScheduleTest' => ScheduleTestCommand::class,
        'ScheduleWork' => ScheduleWorkCommand::class,
        'ScheduleInterrupt' => ScheduleInterruptCommand::class,
        'SchedulePause' => SchedulePauseCommand::class,
        'ScheduleResume' => ScheduleResumeCommand::class,
        'ShowModel' => ShowModelCommand::class,
        'StorageLink' => StorageLinkCommand::class,
        'StorageUnlink' => StorageUnlinkCommand::class,
        'Up' => UpCommand::class,
        'ViewCache' => ViewCacheCommand::class,
        'ViewClear' => ViewClearCommand::class,
        'WebhookDeleteCommand' => WebhookDeleteCommand::class,
        'WebhookDropCommand' => WebhookDropCommand::class,
        'WebhookInfoCommand' => WebhookInfoCommand::class,
        'WebhookSetCommand' => WebhookSetCommand::class,
    ];

    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $devCommands = [
        'ApiInstall' => ApiInstallCommand::class,
        'CacheTable' => CacheTableCommand::class,
        'CastMake' => CastMakeCommand::class,
        'ClassMake' => ClassMakeCommand::class,
        'ComponentMake' => ComponentMakeCommand::class,
        'ConfigMake' => ConfigMakeCommand::class,
        'ConfigPublish' => ConfigPublishCommand::class,
        'ConsoleMake' => ConsoleMakeCommand::class,
        'ControllerMake' => ControllerMakeCommand::class,
        'ConversationMakeCommand' => ConversationMakeCommand::class,
        'Dev' => DevCommand::class,
        'DevList' => DevListCommand::class,
        'EnumMake' => EnumMakeCommand::class,
        'EventGenerate' => EventGenerateCommand::class,
        'EventMake' => EventMakeCommand::class,
        'ExceptionMake' => ExceptionMakeCommand::class,
        'FactoryMake' => FactoryMakeCommand::class,
        'InterfaceMake' => InterfaceMakeCommand::class,
        'JobMake' => JobMakeCommand::class,
        'JobMiddlewareMake' => JobMiddlewareMakeCommand::class,
        'LangPublish' => LangPublishCommand::class,
        'ListenerMake' => ListenerMakeCommand::class,
        'MiddlewareMake' => MiddlewareMakeCommand::class,
        'ModelMake' => ModelMakeCommand::class,
        'ObserverMake' => ObserverMakeCommand::class,
        'PolicyMake' => PolicyMakeCommand::class,
        'ProviderMake' => ProviderMakeCommand::class,
        'QueueFailedTable' => FailedTableCommand::class,
        'QueueTable' => TableCommand::class,
        'QueueBatchesTable' => BatchesTableCommand::class,
        'RequestMake' => RequestMakeCommand::class,
        'ResourceMake' => ResourceMakeCommand::class,
        'RuleMake' => RuleMakeCommand::class,
        'ScopeMake' => ScopeMakeCommand::class,
        'SeederMake' => SeederMakeCommand::class,
        'SessionTable' => SessionTableCommand::class,
        'Serve' => ServeCommand::class,
        'StubPublish' => StubPublishCommand::class,
        'TemplateMakeCommand' => TemplateMakeCommand::class,
        'TraitMake' => TraitMakeCommand::class,
        'VendorPublish' => VendorPublishCommand::class,
        'ViewMake' => ViewMakeCommand::class,
    ];

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
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
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        DevCommands::registerDefaults();
    }

    /**
     * Register the given commands.
     *
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
     * Register the command.
     *
     * @return void
     */
    protected function registerAboutCommand()
    {
        $this->app->singleton(AboutCommand::class, function ($app) {
            return new AboutCommand($app['composer']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerCacheClearCommand()
    {
        $this->app->singleton(CacheClearCommand::class, function ($app) {
            return new CacheClearCommand($app['cache'], $app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerCacheForgetCommand()
    {
        $this->app->singleton(CacheForgetCommand::class, function ($app) {
            return new CacheForgetCommand($app['cache']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerCacheTableCommand()
    {
        $this->app->singleton(CacheTableCommand::class, function ($app) {
            return new CacheTableCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerCastMakeCommand()
    {
        $this->app->singleton(CastMakeCommand::class, function ($app) {
            return new CastMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerClassMakeCommand()
    {
        $this->app->singleton(ClassMakeCommand::class, function ($app) {
            return new ClassMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerComponentMakeCommand()
    {
        $this->app->singleton(ComponentMakeCommand::class, function ($app) {
            return new ComponentMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerConfigCacheCommand()
    {
        $this->app->singleton(ConfigCacheCommand::class, function ($app) {
            return new ConfigCacheCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerConfigClearCommand()
    {
        $this->app->singleton(ConfigClearCommand::class, function ($app) {
            return new ConfigClearCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerConfigMakeCommand()
    {
        $this->app->singleton(ConfigMakeCommand::class, function ($app) {
            return new ConfigMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerConfigPublishCommand()
    {
        $this->app->singleton(ConfigPublishCommand::class, function () {
            return new ConfigPublishCommand;
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerConsoleMakeCommand()
    {
        $this->app->singleton(ConsoleMakeCommand::class, function ($app) {
            return new ConsoleMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerControllerMakeCommand()
    {
        $this->app->singleton(ControllerMakeCommand::class, function ($app) {
            return new ControllerMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerConversationMakeCommand()
    {
        $this->app->singleton(ConversationMakeCommand::class, function ($app) {
            return new ConversationMakeCommand($app['files']);
        });
    }


    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerEnumMakeCommand()
    {
        $this->app->singleton(EnumMakeCommand::class, function ($app) {
            return new EnumMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerEventMakeCommand()
    {
        $this->app->singleton(EventMakeCommand::class, function ($app) {
            return new EventMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerExceptionMakeCommand()
    {
        $this->app->singleton(ExceptionMakeCommand::class, function ($app) {
            return new ExceptionMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerFactoryMakeCommand()
    {
        $this->app->singleton(FactoryMakeCommand::class, function ($app) {
            return new FactoryMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerEventClearCommand()
    {
        $this->app->singleton(EventClearCommand::class, function ($app) {
            return new EventClearCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerInterfaceMakeCommand()
    {
        $this->app->singleton(InterfaceMakeCommand::class, function ($app) {
            return new InterfaceMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerJobMakeCommand()
    {
        $this->app->singleton(JobMakeCommand::class, function ($app) {
            return new JobMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerJobMiddlewareMakeCommand()
    {
        $this->app->singleton(JobMiddlewareMakeCommand::class, function ($app) {
            return new JobMiddlewareMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerListenCacheCommand()
    {
        $this->app->singleton(ListenCacheCommand::class, function ($app) {
            return new ListenCacheCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerListenClearCommand()
    {
        $this->app->singleton(ListenClearCommand::class, function ($app) {
            return new ListenClearCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerListenListCommand()
    {
        $this->app->singleton(ListenListCommand::class, function ($app) {
            return new ListenListCommand($app['listener']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerListenerMakeCommand()
    {
        $this->app->singleton(ListenerMakeCommand::class, function ($app) {
            return new ListenerMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerMiddlewareMakeCommand()
    {
        $this->app->singleton(MiddlewareMakeCommand::class, function ($app) {
            return new MiddlewareMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerModelMakeCommand()
    {
        $this->app->singleton(ModelMakeCommand::class, function ($app) {
            return new ModelMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerObserverMakeCommand()
    {
        $this->app->singleton(ObserverMakeCommand::class, function ($app) {
            return new ObserverMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerPolicyMakeCommand()
    {
        $this->app->singleton(PolicyMakeCommand::class, function ($app) {
            return new PolicyMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerProviderMakeCommand()
    {
        $this->app->singleton(ProviderMakeCommand::class, function ($app) {
            return new ProviderMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerQueueForgetCommand()
    {
        $this->app->singleton(ForgetFailedQueueCommand::class);
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerQueueListenCommand()
    {
        $this->app->singleton(QueueListenCommand::class, function ($app) {
            return new QueueListenCommand($app['queue.listener']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerQueueMonitorCommand()
    {
        $this->app->singleton(QueueMonitorCommand::class, function ($app) {
            return new QueueMonitorCommand($app['queue'], $app['events']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerQueuePruneBatchesCommand()
    {
        $this->app->singleton(QueuePruneBatchesCommand::class, function () {
            return new QueuePruneBatchesCommand;
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerQueuePruneFailedJobsCommand()
    {
        $this->app->singleton(QueuePruneFailedJobsCommand::class, function () {
            return new QueuePruneFailedJobsCommand;
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerQueueRestartCommand()
    {
        $this->app->singleton(QueueRestartCommand::class, function ($app) {
            return new QueueRestartCommand($app['cache.store']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerQueueWorkCommand()
    {
        $this->app->singleton(QueueWorkCommand::class, function ($app) {
            return new QueueWorkCommand($app['queue.worker'], $app['cache.store']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerQueueFailedTableCommand()
    {
        $this->app->singleton(FailedTableCommand::class, function ($app) {
            return new FailedTableCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerQueueTableCommand()
    {
        $this->app->singleton(TableCommand::class, function ($app) {
            return new TableCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerQueueBatchesTableCommand()
    {
        $this->app->singleton(BatchesTableCommand::class, function ($app) {
            return new BatchesTableCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerRequestMakeCommand()
    {
        $this->app->singleton(RequestMakeCommand::class, function ($app) {
            return new RequestMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerResourceMakeCommand()
    {
        $this->app->singleton(ResourceMakeCommand::class, function ($app) {
            return new ResourceMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerRuleMakeCommand()
    {
        $this->app->singleton(RuleMakeCommand::class, function ($app) {
            return new RuleMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerScopeMakeCommand()
    {
        $this->app->singleton(ScopeMakeCommand::class, function ($app) {
            return new ScopeMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerSeederMakeCommand()
    {
        $this->app->singleton(SeederMakeCommand::class, function ($app) {
            return new SeederMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerSessionTableCommand()
    {
        $this->app->singleton(SessionTableCommand::class, function ($app) {
            return new SessionTableCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerRouteCacheCommand()
    {
        $this->app->singleton(RouteCacheCommand::class, function ($app) {
            return new RouteCacheCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerRouteClearCommand()
    {
        $this->app->singleton(RouteClearCommand::class, function ($app) {
            return new RouteClearCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerRouteListCommand()
    {
        $this->app->singleton(RouteListCommand::class, function ($app) {
            return new RouteListCommand($app['router']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerSeedCommand()
    {
        $this->app->singleton(SeedCommand::class, function ($app) {
            return new SeedCommand($app['db']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerTemplateMakeCommand()
    {
        $this->app->singleton(TemplateMakeCommand::class, function ($app) {
            return new TemplateMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerTraitMakeCommand()
    {
        $this->app->singleton(TraitMakeCommand::class, function ($app) {
            return new TraitMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerVendorPublishCommand()
    {
        $this->app->singleton(VendorPublishCommand::class, function ($app) {
            return new VendorPublishCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerViewClearCommand()
    {
        $this->app->singleton(ViewClearCommand::class, function ($app) {
            return new ViewClearCommand($app['files']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array_merge(array_values($this->commands), array_values($this->devCommands));
    }
}
