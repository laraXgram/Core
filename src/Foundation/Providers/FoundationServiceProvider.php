<?php

namespace LaraGram\Foundation\Providers;

use LaraGram\Console\Events\CommandFinished;
use LaraGram\Console\Scheduling\Schedule;
use LaraGram\Contracts\Console\Kernel as ConsoleKernel;
use LaraGram\Contracts\Events\Dispatcher;
use LaraGram\Foundation\Exceptions\Renderer\Listener;
use LaraGram\Queue\Events\JobAttempted;
use LaraGram\Request\Request;
use LaraGram\Support\AggregateServiceProvider;
use LaraGram\Support\Defer\DeferredCallbackCollection;
use LaraGram\Validation\ValidationException;

class FoundationServiceProvider extends AggregateServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->hasDebugModeEnabled()) {
            $this->app->make(Listener::class)->registerListeners(
                $this->app->make(Dispatcher::class)
            );
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        parent::register();

        $this->registerConsoleSchedule();
        $this->registerRequestValidation();
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
     * Register the "validate" macro on the request.
     *
     * @return void
     */
    public function registerRequestValidation()
    {
        Request::macro('validate', function (array $rules, ...$params) {
            return validator($this->all(), $rules, ...$params)->validate();
        });

        Request::macro('validateWithBag', function (string $errorBag, array $rules, ...$params) {
            try {
                return $this->validate($rules, ...$params);
            } catch (ValidationException $e) {
                $e->errorBag = $errorBag;

                throw $e;
            }
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
