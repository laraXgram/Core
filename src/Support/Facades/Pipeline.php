<?php

namespace LaraGram\Support\Facades;

/**
 * @method static \LaraGram\Pipeline\Pipeline send(mixed $passable)
 * @method static \LaraGram\Pipeline\Pipeline through(array|mixed $pipes)
 * @method static \LaraGram\Pipeline\Pipeline pipe(array|mixed $pipes)
 * @method static \LaraGram\Pipeline\Pipeline via(string $method)
 * @method static mixed then(\Closure $destination)
 * @method static mixed thenReturn()
 * @method static \LaraGram\Pipeline\Pipeline finally(\Closure $callback)
 * @method static \LaraGram\Pipeline\Pipeline setContainer(\LaraGram\Contracts\Container\Container $container)
 * @method static \LaraGram\Pipeline\Pipeline|mixed when(\Closure|mixed|null $value = null, callable|null $callback = null, callable|null $default = null)
 * @method static \LaraGram\Pipeline\Pipeline|mixed unless(\Closure|mixed|null $value = null, callable|null $callback = null, callable|null $default = null)
 *
 * @see \LaraGram\Pipeline\Pipeline
 */
class Pipeline extends Facade
{
    /**
     * Indicates if the resolved instance should be cached.
     *
     * @var bool
     */
    protected static $cached = false;

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'pipeline';
    }
}
