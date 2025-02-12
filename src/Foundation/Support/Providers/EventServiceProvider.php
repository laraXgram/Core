<?php

namespace LaraGram\Foundation\Support\Providers;

use LaraGram\Foundation\Events\DiscoverEvents;
use LaraGram\Support\Arr;
use LaraGram\Support\Facades\Event;
use LaraGram\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [];

    /**
     * The subscribers to register.
     *
     * @var array
     */
    protected $subscribe = [];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * The configured event discovery paths.
     *
     * @var array|null
     */
    protected static $eventDiscoveryPaths;

    /**
     * Register the application's event listeners.
     *
     * @return void
     */
    public function register()
    {
        $this->booting(function () {
            $events = $this->getEvents();

            foreach ($events as $event => $listeners) {
                foreach (array_unique($listeners, SORT_REGULAR) as $listener) {
                    Event::listen($event, $listener);
                }
            }

            foreach ($this->subscribe as $subscriber) {
                Event::subscribe($subscriber);
            }
        });
    }

    /**
     * Boot any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Get the events and handlers.
     *
     * @return array
     */
    public function listens()
    {
        return $this->listen;
    }

    /**
     * Get the discovered events and listeners for the application.
     *
     * @return array
     */
    public function getEvents()
    {
        if ($this->app->eventsAreCached()) {
            $cache = require $this->app->getCachedEventsPath();

            return $cache[get_class($this)] ?? [];
        } else {
            return array_merge_recursive(
                $this->discoveredEvents(),
                $this->listens()
            );
        }
    }

    /**
     * Get the discovered events for the application.
     *
     * @return array
     */
    protected function discoveredEvents()
    {
        return $this->shouldDiscoverEvents()
            ? $this->discoverEvents()
            : [];
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        return get_class($this) === __CLASS__ && static::$shouldDiscoverEvents === true;
    }

    /**
     * Discover the events and listeners for the application.
     *
     * @return array
     */
    public function discoverEvents()
    {
        $directories = array_map(function ($directory) {
            return glob($directory, GLOB_ONLYDIR);
        }, $this->discoverEventsWithin());

        $directories = array_merge(...$directories);

        $directories = array_filter($directories, function ($directory) {
            return is_dir($directory);
        });

        $discovered = [];

        foreach ($directories as $directory) {
            $discovered = array_merge_recursive(
                $discovered,
                DiscoverEvents::within($directory, $this->eventDiscoveryBasePath())
            );
        }

        return $discovered;

    }

    /**
     * Get the listener directories that should be used to discover events.
     *
     * @return array
     */
    protected function discoverEventsWithin()
    {
        return static::$eventDiscoveryPaths ?: [
            $this->app->appPath('Listeners'),
        ];
    }

    /**
     * Add the given event discovery paths to the application's event discovery paths.
     *
     * @param  string|array  $paths
     * @return void
     */
    public static function addEventDiscoveryPaths(array|string $paths)
    {
        static::$eventDiscoveryPaths = array_values(array_unique(
            array_merge(static::$eventDiscoveryPaths, Arr::wrap($paths))
        ));
    }

    /**
     * Set the globally configured event discovery paths.
     *
     * @param  array  $paths
     * @return void
     */
    public static function setEventDiscoveryPaths(array $paths)
    {
        static::$eventDiscoveryPaths = $paths;
    }

    /**
     * Get the base path to be used during event discovery.
     *
     * @return string
     */
    protected function eventDiscoveryBasePath()
    {
        return $this->app->basePath();
    }

    /**
     * Disable event discovery for the application.
     *
     * @return void
     */
    public static function disableEventDiscovery()
    {
        static::$shouldDiscoverEvents = false;
    }
}
