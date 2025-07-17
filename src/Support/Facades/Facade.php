<?php

namespace LaraGram\Support\Facades;

use Closure;
use LaraGram\Support\Arr;
use LaraGram\Support\Collection;
use LaraGram\Support\Number;
use LaraGram\Support\Str;

abstract class Facade
{
    /**
     * The application instance being facaded.
     *
     * @var \LaraGram\Contracts\Foundation\Application|null
     */
    protected static $app;

    /**
     * The resolved object instances.
     *
     * @var array
     */
    protected static $resolvedInstance;

    /**
     * Indicates if the resolved instance should be cached.
     *
     * @var bool
     */
    protected static $cached = true;

    /**
     * Run a Closure when the facade has been resolved.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public static function resolved(Closure $callback)
    {
        $accessor = static::getFacadeAccessor();

        if (static::$app->resolved($accessor) === true) {
            $callback(static::getFacadeRoot(), static::$app);
        }

        static::$app->afterResolving($accessor, function ($service, $app) use ($callback) {
            $callback($service, $app);
        });
    }

    /**
     * Hotswap the underlying instance behind the facade.
     *
     * @param  mixed  $instance
     * @return void
     */
    public static function swap($instance)
    {
        static::$resolvedInstance[static::getFacadeAccessor()] = $instance;

        if (isset(static::$app)) {
            static::$app->instance(static::getFacadeAccessor(), $instance);
        }
    }

    /**
     * Get the root object behind the facade.
     *
     * @return mixed
     */
    public static function getFacadeRoot()
    {
        return static::resolveFacadeInstance(static::getFacadeAccessor());
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    protected static function getFacadeAccessor()
    {
        throw new RuntimeException('Facade does not implement getFacadeAccessor method.');
    }

    /**
     * Resolve the facade root instance from the container.
     *
     * @param  string  $name
     * @return mixed
     */
    protected static function resolveFacadeInstance($name)
    {
        if (isset(static::$resolvedInstance[$name])) {
            return static::$resolvedInstance[$name];
        }

        if (static::$app) {
            if (static::$cached) {
                return static::$resolvedInstance[$name] = static::$app[$name];
            }

            return static::$app[$name];
        }
    }

    /**
     * Clear a resolved facade instance.
     *
     * @param  string  $name
     * @return void
     */
    public static function clearResolvedInstance($name)
    {
        unset(static::$resolvedInstance[$name]);
    }

    /**
     * Clear all of the resolved instances.
     *
     * @return void
     */
    public static function clearResolvedInstances()
    {
        static::$resolvedInstance = [];
    }

    /**
     * Get the application default aliases.
     *
     * @return \LaraGram\Support\Collection
     */
    public static function defaultAliases()
    {
        return new Collection([
            'App' => App::class,
            'Arr' => Arr::class,
            'Auth' => Auth::class,
            'Bot' => Bot::class,
            'Bus' => Bus::class,
            'Cache' => Cache::class,
            'Commander' => Commander::class,
            'Concurrency' => Concurrency::class,
            'Config' => Config::class,
            'Context' => Context::class,
            'Conversation' => Conversation::class,
            'Crypt' => Crypt::class,
            'Date' => Date::class,
            'DB' => DB::class,
            'Event' => Event::class,
            'File' => File::class,
            'Gate' => Gate::class,
            'Hash' => Hash::class,
            'Lang' => Lang::class,
            'Log' => Log::class,
            'Number' => Number::class,
            'Process' => Process::class,
            'Queue' => Queue::class,
            'RateLimiter' => RateLimiter::class,
            'Redirect' => Redirect::class,
            'Request' => Request::class,
            'Schedule' => Schedule::class,
            'Schema' => Schema::class,
            'Step' => Step::class,
            'Str' => Str::class,
            'Template' => Template::class,
            'Validator' => Validator::class,
        ]);
    }

    /**
     * Get the application instance behind the facade.
     *
     * @return \LaraGram\Contracts\Foundation\Application|null
     */
    public static function getFacadeApplication()
    {
        return static::$app;
    }

    /**
     * Set the application instance.
     *
     * @param  \LaraGram\Contracts\Foundation\Application|null  $app
     * @return void
     */
    public static function setFacadeApplication($app)
    {
        static::$app = $app;
    }

    /**
     * Handle dynamic, static calls to the object.
     *
     * @param  string  $method
     * @param  array  $args
     * @return mixed
     *
     * @throws \RuntimeException
     */
    public static function __callStatic($method, $args)
    {
        $instance = static::getFacadeRoot();

        if (! $instance) {
            throw new RuntimeException('A facade root has not been set.');
        }

        return $instance->$method(...$args);
    }
}