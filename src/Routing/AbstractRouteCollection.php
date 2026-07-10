<?php

namespace LaraGram\Routing;

use ArrayIterator;
use Countable;
use LaraGram\Http\Request;
use LaraGram\Http\Response;
use LaraGram\Support\Str;
use IteratorAggregate;
use LogicException;
use LaraGram\Foundation\Http\Exceptions\MethodNotAllowedHttpException;
use LaraGram\Foundation\Http\Exceptions\NotFoundHttpException;
use LaraGram\Routing\Matcher\Dumper\CompiledUrlMatcherDumper;
use Traversable;

abstract class AbstractRouteCollection implements Countable, IteratorAggregate, RouteCollectionInterface
{
    /**
     * Handle the matched route.
     *
     * @param  \LaraGram\Http\Request  $request
     * @param  \LaraGram\Routing\Route|null  $route
     * @return \LaraGram\Routing\Route
     *
     * @throws \LaraGram\Foundation\Http\Exceptions\NotFoundHttpException
     */
    protected function handleMatchedRoute(Request $request, $route)
    {
        if (! is_null($route)) {
            return $route->bind($request);
        }

        // If no route was found we will now check if a matching route is specified by
        // another HTTP verb. If it is we will need to throw a MethodNotAllowed and
        // inform the user agent of which HTTP verb it should use for this route.
        $others = $this->checkForAlternateVerbs($request);

        if (count($others) > 0) {
            return $this->getRouteForMethods($request, $others);
        }

        throw new NotFoundHttpException(sprintf(
            'The route %s could not be found.',
            $request->path()
        ));
    }

    /**
     * Determine if any routes match on another HTTP verb.
     *
     * @param  \LaraGram\Http\Request  $request
     * @return array
     */
    protected function checkForAlternateVerbs($request)
    {
        $methods = array_diff(Router::$verbs, [$request->getMethod()]);

        // Here we will spin through all verbs except for the current request verb and
        // check to see if any routes respond to them. If they do, we will return a
        // proper error response with the correct headers on the response string.
        return array_values(array_filter(
            $methods,
            function ($method) use ($request) {
                return ! is_null($this->matchAgainstRoutes($this->get($method), $request, false));
            }
        ));
    }

    /**
     * Determine if a route in the array matches the request.
     *
     * @param  \LaraGram\Routing\Route[]  $routes
     * @param  \LaraGram\Http\Request  $request
     * @param  bool  $includingMethod
     * @return \LaraGram\Routing\Route|null
     */
    protected function matchAgainstRoutes(array $routes, $request, $includingMethod = true)
    {
        $fallbackRoute = null;

        foreach ($routes as $route) {
            if ($route->matches($request, $includingMethod)) {
                if ($route->isFallback) {
                    $fallbackRoute ??= $route;

                    continue;
                }

                return $route;
            }
        }

        return $fallbackRoute;
    }

    /**
     * Get a route (if necessary) that responds when other available methods are present.
     *
     * @param  \LaraGram\Http\Request  $request
     * @param  string[]  $methods
     * @return \LaraGram\Routing\Route
     *
     * @throws \LaraGram\Foundation\Http\Exceptions\MethodNotAllowedHttpException
     */
    protected function getRouteForMethods($request, array $methods)
    {
        if ($request->isMethod('OPTIONS')) {
            return (new Route('OPTIONS', $request->path(), function () use ($methods) {
                return new Response('', 200, ['Allow' => implode(',', $methods)]);
            }))->bind($request);
        }

        $this->requestMethodNotAllowed($request, $methods, $request->method());
    }

    /**
     * Throw a method not allowed HTTP exception.
     *
     * @param  \LaraGram\Http\Request  $request
     * @param  array  $others
     * @param  string  $method
     * @return never
     *
     * @throws \LaraGram\Foundation\Http\Exceptions\MethodNotAllowedHttpException
     */
    protected function requestMethodNotAllowed($request, array $others, $method)
    {
        throw new MethodNotAllowedHttpException(
            $others,
            sprintf(
                'The %s method is not supported for route %s. Supported methods: %s.',
                $method,
                $request->path(),
                implode(', ', $others)
            )
        );
    }

    /**
     * Throw a method not allowed HTTP exception.
     *
     * @param  array  $others
     * @param  string  $method
     * @return void
     *
     * @deprecated use requestMethodNotAllowed
     *
     * @throws \LaraGram\Foundation\Http\Exceptions\MethodNotAllowedHttpException
     */
    protected function methodNotAllowed(array $others, $method)
    {
        throw new MethodNotAllowedHttpException(
            $others,
            sprintf(
                'The %s method is not supported for this route. Supported methods: %s.',
                $method,
                implode(', ', $others)
            )
        );
    }

    /**
     * Compile the routes for caching.
     *
     * @return array
     */
    public function compile()
    {
        $compiled = $this->dumper()->getCompiledRoutes();

        $attributes = [];

        foreach ($this->getRoutes() as $route) {
            $attributes[$route->getName()] = [
                'methods' => $route->methods(),
                'uri' => $route->uri(),
                'action' => $route->getAction(),
                'fallback' => $route->isFallback,
                'defaults' => $route->defaults,
                'wheres' => $route->wheres,
                'bindingFields' => $route->bindingFields(),
                'lockSeconds' => $route->locksFor(),
                'waitSeconds' => $route->waitsFor(),
                'withTrashed' => $route->allowsTrashedBindings(),
            ];
        }

        return ['compiled' => $compiled, 'attributes' => $attributes];
    }

    /**
     * Return the CompiledUrlMatcherDumper instance for the route collection.
     *
     * @return \LaraGram\Routing\Matcher\Dumper\CompiledUrlMatcherDumper
     */
    public function dumper()
    {
        return new CompiledUrlMatcherDumper($this->toLaraGramRouteCollection());
    }

    /**
     * Convert the collection to a LaraGram RouteCollection instance.
     *
     * @return \LaraGram\Routing\BaseRouteCollection
     */
    public function toLaraGramRouteCollection()
    {
        $laraGramRoutes = new BaseRouteCollection;

        $fallbackRoutes = [];

        foreach ($this->getRoutes() as $route) {
            if ($route->isFallback) {
                $fallbackRoutes[] = $route;

                continue;
            }

            $laraGramRoutes = $this->addToLaraGramRoutesCollection($laraGramRoutes, $route);
        }

        foreach ($fallbackRoutes as $route) {
            $laraGramRoutes = $this->addToLaraGramRoutesCollection($laraGramRoutes, $route);
        }

        return $laraGramRoutes;
    }

    /**
     * Add a route to the LaraGramRouteCollection instance.
     *
     * @param  \LaraGram\Routing\BaseRouteCollection  $laraGramRoutes
     * @param  \LaraGram\Routing\Route  $route
     * @return \LaraGram\Routing\BaseRouteCollection
     *
     * @throws \LogicException
     */
    protected function addToLaraGramRoutesCollection(BaseRouteCollection $laraGramRoutes, Route $route)
    {
        $name = $route->getName();

        if (
            ! is_null($name)
            && str_ends_with($name, '.')
            && ! is_null($laraGramRoutes->get($name))
        ) {
            $name = null;
        }

        if (! $name) {
            $route->name($this->generateRouteName());

            $this->add($route);
        } elseif (! is_null($laraGramRoutes->get($name))) {
            throw new LogicException("Unable to prepare route [{$route->uri}] for serialization. Another route has already been assigned name [{$name}].");
        }

        $laraGramRoutes->add($route->getName(), $route->toLaraGramRoute());

        return $laraGramRoutes;
    }

    /**
     * Get a randomly generated route name.
     *
     * @return string
     */
    protected function generateRouteName()
    {
        return 'generated::'.Str::random();
    }

    /**
     * Get an iterator for the items.
     *
     * @return \ArrayIterator
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->getRoutes());
    }

    /**
     * Count the number of items in the collection.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->getRoutes());
    }
}
