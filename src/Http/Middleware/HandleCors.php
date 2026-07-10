<?php

namespace LaraGram\Http\Middleware;

use Closure;
use LaraGram\Http\CorsService;
use LaraGram\Contracts\Container\Container;
use LaraGram\Http\Request;

class HandleCors
{
    /**
     * The container instance.
     *
     * @var \LaraGram\Contracts\Container\Container
     */
    protected $container;

    /**
     * The CORS service instance.
     *
     * @var \LaraGram\Http\CorsService
     */
    protected $cors;

    /**
     * All of the registered skip callbacks.
     *
     * @var array<int, \Closure(\LaraGram\Http\Request): bool>
     */
    protected static $skipCallbacks = [];

    /**
     * Create a new middleware instance.
     *
     * @param  \LaraGram\Contracts\Container\Container  $container
     * @param  \LaraGram\Http\CorsService  $cors
     */
    public function __construct(Container $container, CorsService $cors)
    {
        $this->container = $container;
        $this->cors = $cors;
    }

    /**
     * Handle the incoming request.
     *
     * @param  \LaraGram\Http\Request  $request
     * @param  \Closure  $next
     * @return \LaraGram\Http\Response
     */
    public function handle($request, Closure $next)
    {
        foreach (static::$skipCallbacks as $callback) {
            if ($callback($request)) {
                return $next($request);
            }
        }

        if (! $this->hasMatchingPath($request)) {
            return $next($request);
        }

        $this->cors->setOptions($this->container['config']->get('cors', []));

        if ($this->cors->isPreflightRequest($request)) {
            $response = $this->cors->handlePreflightRequest($request);

            $this->cors->varyHeader($response, 'Access-Control-Request-Method');

            return $response;
        }

        $response = $next($request);

        if ($request->getMethod() === 'OPTIONS') {
            $this->cors->varyHeader($response, 'Access-Control-Request-Method');
        }

        return $this->cors->addActualRequestHeaders($response, $request);
    }

    /**
     * Get the path from the configuration to determine if the CORS service should run.
     *
     * @param  \LaraGram\Http\Request  $request
     * @return bool
     */
    protected function hasMatchingPath(Request $request): bool
    {
        $paths = $this->getPathsByHost($request->getHost());

        foreach ($paths as $path) {
            if ($path !== '/') {
                $path = trim($path, '/');
            }

            if ($request->fullUrlIs($path) || $request->is($path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the CORS paths for the given host.
     *
     * @param  string  $host
     * @return array
     */
    protected function getPathsByHost(string $host)
    {
        $paths = $this->container['config']->get('cors.paths', []);

        return $paths[$host] ?? array_filter($paths, function ($path) {
            return is_string($path);
        });
    }

    /**
     * Register a callback that instructs the middleware to be skipped.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public static function skipWhen(Closure $callback)
    {
        static::$skipCallbacks[] = $callback;
    }

    /**
     * Flush the middleware's global state.
     *
     * @return void
     */
    public static function flushState()
    {
        static::$skipCallbacks = [];
    }
}
