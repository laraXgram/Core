<?php

namespace LaraGram\Foundation\Configuration;

use Closure;
use LaraGram\Console\Application as Commander;
use LaraGram\Console\Scheduling\Schedule;
use LaraGram\Foundation\Application;
use LaraGram\Foundation\Bootstrap\RegisterProviders;
use LaraGram\Foundation\Events\DiagnosingHealth;
use LaraGram\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;
use LaraGram\Foundation\Support\Providers\EventServiceProvider as AppEventServiceProvider;
use LaraGram\Foundation\Support\Providers\ListenServiceProvider as AppListenServiceProvider;
use LaraGram\Foundation\Support\Providers\RouteServiceProvider as AppRouteServiceProvider;
use LaraGram\Http\Middleware\PrefersJsonResponses;
use LaraGram\Http\Request;
use LaraGram\Support\Collection;
use LaraGram\Support\Facades\Bot;
use LaraGram\Support\Facades\Event;
use LaraGram\Support\Facades\Route;
use LaraGram\Support\Facades\View;

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
     * Any additional routing callbacks that should be invoked while registering routes.
     *
     * @var array
     */
    protected array $additionalRoutingCallbacks = [];

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
            \LaraGram\Contracts\Http\Kernel::class,
            \LaraGram\Foundation\Http\Kernel::class,
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
     * Register the routing services for the application.
     *
     * @param  \Closure|null  $using
     * @param  array|string|null  $web
     * @param  array|string|null  $api
     * @param  string|null  $commands
     * @param  string|null  $pages
     * @param  string|null  $health
     * @param  string  $apiPrefix
     * @param  callable|null  $then
     * @return $this
     */
    public function withRouting(?Closure $using = null,
                                array|string|null $web = null,
                                array|string|null $api = null,
                                ?string $commands = null,
                                ?string $health = null,
                                string $apiPrefix = 'api',
                                ?callable $then = null)
    {
        if (is_null($using) && (is_string($web) || is_array($web) || is_string($api) || is_array($api) || is_string($health)) || is_callable($then)) {
            $using = $this->buildRoutingCallback($web, $api, $health, $apiPrefix, $then);

            if (is_string($health)) {
                PreventRequestsDuringMaintenance::except($health);
            }
        }

        AppRouteServiceProvider::loadRoutesUsing($using);

        $this->app->booting(function () {
            $this->app->register(AppRouteServiceProvider::class, force: true);
        });

        if (is_string($commands) && realpath($commands) !== false) {
            $this->withCommands([$commands]);
        }

        return $this;
    }

    /**
     * Create the routing callback for the application.
     *
     * @param  array|string|null  $web
     * @param  array|string|null  $api
     * @param  string|null  $health
     * @param  string  $apiPrefix
     * @param  callable|null  $then
     * @return \Closure
     *
     * @throws \Throwable
     */
    protected function buildRoutingCallback(array|string|null $web,
                                            array|string|null $api,
                                            ?string $health,
                                            string $apiPrefix,
                                            ?callable $then)
    {
        return function () use ($web, $api, $health, $apiPrefix, $then) {
            if (is_string($api) || is_array($api)) {
                if (is_array($api)) {
                    foreach ($api as $apiRoute) {
                        if (realpath($apiRoute) !== false) {
                            Route::middleware('api')->prefix($apiPrefix)->group($apiRoute);
                        }
                    }
                } else {
                    Route::middleware('api')->prefix($apiPrefix)->group($api);
                }
            }

            if (is_string($health)) {
                Route::get($health, function (Request $request) {
                    $exception = null;

                    try {
                        Event::dispatch(new DiagnosingHealth);
                    } catch (\Throwable $e) {
                        if (app()->hasDebugModeEnabled()) {
                            throw $e;
                        }

                        report($e);

                        $exception = $e->getMessage();
                    }

                    $status = $exception ? 500 : 200;

                    if ($request->expectsJson()) {
                        return response()->json([
                            'status' => $exception ? 'down' : 'up',
                        ], $status);
                    }

                    return response(View::file(__DIR__.'/../resources/health-up.blade.php', [
                        'exception' => $exception,
                    ]), status: $status);
                });
            }

            if (is_string($web) || is_array($web)) {
                if (is_array($web)) {
                    foreach ($web as $webRoute) {
                        if (realpath($webRoute) !== false) {
                            Route::middleware('web')->group($webRoute);
                        }
                    }
                } else {
                    Route::middleware('web')->group($web);
                }
            }

            foreach ($this->additionalRoutingCallbacks as $callback) {
                $callback();
            }

            if (is_callable($then)) {
                $then($this->app);
            }
        };
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
                                array|string|null $client = null,
                                ?callable $then = null)
    {
        if (is_null($using) && (is_string($bot) || is_array($bot) || is_string($client) || is_array($client) || is_callable($then))) {
            $using = $this->buildListeningCallback($bot, $client, $then);
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
     * @param array|string|null $bot
     * @param array|string|null $client
     * @param callable|null $then
     * @return \Closure
     */
    protected function buildListeningCallback(array|string|null $bot, array|string|null $client, ?callable $then)
    {
        return function () use ($bot, $client, $then) {
            if (is_string($bot) || is_array($bot)) {
                if (is_array($bot)) {
                    foreach ($bot as $file => $connections) {
                        if (is_int($file)) {
                            $file = $connections;
                            $connections = ['*'];
                        } else {
                            $connections = (array) $connections;
                        }

                        if (realpath($file) !== false) {
                            Bot::middleware('bot')->forConnections($connections)->group($file);
                        }
                    }
                } else {
                    Bot::middleware('bot')->group($bot);
                }
            }

            if ((is_string($client) || is_array($client)) && $this->app->bound('client.listener')) {
                $clientListener = $this->app->make('client.listener');
                if (is_array($client)) {
                    foreach ($client as $clientListen) {
                        if (realpath($clientListen) !== false) {
                            $clientListener->group(['middleware' => ['client']], $clientListen);
                        }
                    }
                } else {
                    if (realpath($client) !== false) {
                        $clientListener->group(['middleware' => ['client']], $client);
                    }
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
        $resolver = function ($kernel) use ($callback) {
            $middleware = new Middleware;

            if ($callback) {
                $callback($middleware);
            }

            $kernel->setGlobalMiddleware($middleware->getGlobalMiddleware());
            $kernel->setMiddlewareGroups($middleware->getMiddlewareGroups());
            $kernel->setMiddlewareAliases($middleware->getMiddlewareAliases());

            if ($priorities = $middleware->getMiddlewarePriority()) {
                $kernel->setMiddlewarePriority($priorities);
            }

            foreach ($middleware->getMiddlewarePriorityAppends() as $newMiddleware => $after) {
                $kernel->addToMiddlewarePriorityAfter($after, $newMiddleware);
            }

            foreach ($middleware->getMiddlewarePriorityPrepends() as $newMiddleware => $before) {
                $kernel->addToMiddlewarePriorityBefore($before, $newMiddleware);
            }
        };

        foreach ([
                     \LaraGram\Contracts\Bot\Kernel::class,
                     \LaraGram\Contracts\Http\Kernel::class,
                 ] as $abstract) {
            $this->app->afterResolving($abstract, $resolver);
        }

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
     * Register additional Artisan route paths.
     *
     * @param  array  $paths
     * @return $this
     */
    protected function withCommandRouting(array $paths)
    {
        $this->app->afterResolving(\LaraGram\Contracts\Console\Kernel::class, function ($kernel) use ($paths) {
            $this->app->booted(fn () => $kernel->addCommandRoutePaths($paths));
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
        Commander::starting(function () use ($callback) {
            $this->app->afterResolving(Schedule::class, fn ($schedule) => $callback($schedule));

            if ($this->app->resolved(Schedule::class)) {
                $callback($this->app->make(Schedule::class));
            }
        });
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

        if ($using !== null) {
            $this->app->afterResolving(
                \LaraGram\Foundation\Exceptions\Handler::class,
                fn ($handler) => $using(new Exceptions($handler)),
            );
        }

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
     * Register an array of scoped singleton container bindings to be bound when the application is booting.
     *
     * @param  array  $scopedSingletons
     * @return $this
     */
    public function withScopedSingletons(array $scopedSingletons)
    {
        return $this->registered(function ($app) use ($scopedSingletons) {
            foreach ($scopedSingletons as $abstract => $concrete) {
                if (is_string($abstract)) {
                    $app->scoped($abstract, $concrete);
                } else {
                    $app->scoped($concrete);
                }
            }
        });
    }

    /**
     * Globally prefer JSON responses when the incoming "Accept" header is broad.
     *
     * @param  bool  $prefer
     * @return $this
     */
    public function prefersJsonResponses(bool $prefer = true)
    {
        if (! $prefer) {
            return $this;
        }

        $this->app->booted(function () {
            $this->app->make(\LaraGram\Foundation\Http\Kernel::class)->prependMiddleware(PrefersJsonResponses::class);
        });

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
        return $this->app;
    }
}
