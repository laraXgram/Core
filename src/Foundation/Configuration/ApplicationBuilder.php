<?php

namespace LaraGram\Foundation\Configuration;

use Closure;
use LaraGram\Console\Application as Commander;
use LaraGram\Console\Scheduling\Schedule;
use LaraGram\Foundation\Application;
use LaraGram\Foundation\Bootstrap\RegisterProviders;
use LaraGram\Foundation\Support\Providers\EventServiceProvider as AppEventServiceProvider;
use LaraGram\Foundation\Support\Providers\ListenServiceProvider as AppListenServiceProvider;
use LaraGram\Support\Collection;
use LaraGram\Support\Facades\Bot;

class ApplicationBuilder
{
    /**
     * The service provider that are marked for registration.
     *
     * @var array
     */
    protected array $pendingProviders = [];

    /**
     * Any additional listening callbacks that should be invoked while registering listens.
     *
     * @var array
     */
    protected array $additionalListeningCallbacks = [];

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
            \LaraGram\Contracts\Bot\Kernel::class,
            \LaraGram\Foundation\Bot\Kernel::class,
        );

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
     * Register the listener services for the application.
     *
     * @param  \Closure|null  $using
     * @param  array|string|null  $bot
     * @param  string|null  $commands
     * @param  string|null  $channels
     * @param  string|null  $pages
     * @param  callable|null  $then
     * @return $this
     */
    public function withListener(?Closure $using = null,
                                array|string|null $bot = null,
                                ?string $commands = null,
                                ?callable $then = null)
    {
        if (is_null($using) && (is_string($bot) || is_array($bot) || is_callable($then))) {
            $using = $this->buildListeningCallback($bot, $then);
        }

        AppListenServiceProvider::loadListensUsing($using);

        $this->app->booting(function () {
            $this->app->register(AppListenServiceProvider::class, force: true);
        });

        if (is_string($commands) && realpath($commands) !== false) {
            $this->withCommands([$commands]);
        }

        return $this;
    }

    /**
     * Create the listening callback for the application.
     *
     * @param  array|string|null  $web
     * @param  array|string|null  $api
     * @param  string|null  $pages
     * @param  string|null  $health
     * @param  string  $apiPrefix
     * @param  callable|null  $then
     * @return \Closure
     */
    protected function buildListeningCallback(array|string|null $bot, ?callable $then)
    {
        return function () use ($bot, $then) {
            if (is_string($bot) || is_array($bot)) {
                if (is_array($bot)) {
                    foreach ($bot as $botListen) {
                        if (realpath($botListen) !== false) {
                            Bot::middleware('bot')->group($botListen);
                        }
                    }
                } else {
                    Bot::middleware('bot')->group($bot);
                }
            }

            foreach ($this->additionalListeningCallbacks as $callback) {
                $callback();
            }

            if (is_callable($then)) {
                $then($this->app);
            }
        };
    }

    /**
     * Register the global middleware, middleware groups, and middleware aliases for the application.
     *
     * @param  callable|null  $callback
     * @return $this
     */
    public function withMiddleware(?callable $callback = null)
    {
        $this->app->afterResolving(\LaraGram\Contracts\Bot\Kernel::class, function ($kernel) use ($callback) {
            $middleware = new Middleware;

            if (! is_null($callback)) {
                $callback($middleware);
            }

            $kernel->setGlobalMiddleware($middleware->getGlobalMiddleware());
            $kernel->setMiddlewareGroups($middleware->getMiddlewareGroups());
            $kernel->setMiddlewareAliases($middleware->getMiddlewareAliases());

            if ($priorities = $middleware->getMiddlewarePriority()) {
                $kernel->setMiddlewarePriority($priorities);
            }

            if ($priorityAppends = $middleware->getMiddlewarePriorityAppends()) {
                foreach ($priorityAppends as $newMiddleware => $after) {
                    $kernel->addToMiddlewarePriorityAfter($after, $newMiddleware);
                }
            }

            if ($priorityPrepends = $middleware->getMiddlewarePriorityPrepends()) {
                foreach ($priorityPrepends as $newMiddleware => $before) {
                    $kernel->addToMiddlewarePriorityBefore($before, $newMiddleware);
                }
            }
        });

        return $this;
    }

    /**
     * Register additional Commander commands with the application.
     *
     * @param  array  $commands
     * @return $this
     */
    public function withCommands(array $commands = [])
    {
        if (empty($commands)) {
            $commands = [$this->app->path('Console/Commands')];
        }

        $this->app->afterResolving(\LaraGram\Contracts\Console\Kernel::class, function ($kernel) use ($commands) {
            [$commands, $paths] = (new Collection($commands))->partition(fn ($command) => class_exists($command));
            [$listens, $paths] = $paths->partition(fn ($path) => is_file($path));

            $this->app->booted(static function () use ($kernel, $commands, $paths, $listens) {
                $kernel->addCommands($commands->all());
                $kernel->addCommandPaths($paths->all());
                $kernel->addCommandListenPaths($listens->all());
            });
        });

        return $this;
    }

    /**
     * Register additional Commander listen paths.
     *
     * @param  array  $paths
     * @return $this
     */
    protected function withCommandListening(array $paths)
    {
        $this->app->afterResolving(\LaraGram\Contracts\Console\Kernel::class, function ($kernel) use ($paths) {
            $this->app->booted(fn () => $kernel->addCommandListenPaths($paths));
        });

        return $this;
    }

    /**
     * Register the scheduled tasks for the application.
     *
     * @param  callable(\LaraGram\Console\Scheduling\Schedule $schedule): void  $callback
     * @return $this
     */
    public function withSchedule(callable $callback)
    {
        Commander::starting(fn () => $callback($this->app->make(Schedule::class)));

        return $this;
    }

    /**
     * Register and configure the application's exception handler.
     *
     * @param  callable|null  $using
     * @return $this
     */
    public function withExceptions(?callable $using = null)
    {
        $this->app->singleton(
            \LaraGram\Contracts\Debug\ExceptionHandler::class,
            \LaraGram\Foundation\Exceptions\Handler::class
        );

        $using ??= fn () => true;

        $this->app->afterResolving(
            \LaraGram\Foundation\Exceptions\Handler::class,
            fn ($handler) => $using(new Exceptions($handler)),
        );

        return $this;
    }

    /**
     * Register an array of container bindings to be bound when the application is booting.
     *
     * @param  array  $bindings
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
        return $this->app;
    }
}
