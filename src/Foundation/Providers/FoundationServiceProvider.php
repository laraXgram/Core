<?php

namespace LaraGram\Foundation\Providers;

use LaraGram\Console\Events\CommandFinished;
use LaraGram\Console\Scheduling\Schedule;
use LaraGram\Contracts\Console\Kernel as ConsoleKernel;
use LaraGram\Queue\Events\JobAttempted;
use LaraGram\Support\AggregateServiceProvider;
use LaraGram\Support\Defer\DeferredCallbackCollection;

class FoundationServiceProvider extends AggregateServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        parent::register();

        $this->registerConsoleSchedule();
        $this->registerDeferHandler();
    }

    /**
     * Register the console schedule implementation.
     *
     * @return void
     */
    public function registerConsoleSchedule()
    {
        $this->app->singleton(Schedule::class, function ($app) {
            return $app->make(ConsoleKernel::class)->resolveConsoleSchedule();
        });
    }

    /**
     * Register the "defer" function termination handler.
     *
     * @return void
     */
    protected function registerDeferHandler()
    {
        $this->app->scoped(DeferredCallbackCollection::class);

        $this->app['events']->listen(function (CommandFinished $event) {
            app(DeferredCallbackCollection::class)->invokeWhen(fn ($callback) => app()->runningInConsole() && ($event->exitCode === 0 || $callback->always)
            );
        });

        $this->app['events']->listen(function (JobAttempted $event) {
            app(DeferredCallbackCollection::class)->invokeWhen(fn ($callback) => $event->connectionName !== 'sync' && ($event->successful() || $callback->always)
            );
        });
    }
}
