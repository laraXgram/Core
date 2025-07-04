<?php

namespace LaraGram\Listening;

use LaraGram\Container\Container;
use LaraGram\Listening\Contracts\ControllerDispatcher as ControllerDispatcherContract;
use LaraGram\Support\Collection;

class ControllerDispatcher implements ControllerDispatcherContract
{
    use FiltersControllerMiddleware, ResolvesListenDependencies;

    /**
     * The container instance.
     *
     * @var \LaraGram\Container\Container
     */
    protected $container;

    /**
     * Create a new controller dispatcher instance.
     *
     * @param  \LaraGram\Container\Container  $container
     * @return void
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Dispatch a request to a given controller and method.
     *
     * @param  \LaraGram\Listening\Listen  $listen
     * @param  mixed  $controller
     * @param  string  $method
     * @return mixed
     */
    public function dispatch(Listen $listen, $controller, $method)
    {
        $parameters = $this->resolveParameters($listen, $controller, $method);

        if (method_exists($controller, 'callAction')) {
            return $controller->callAction($method, $parameters);
        }

        return $controller->{$method}(...array_values($parameters));
    }

    /**
     * Resolve the parameters for the controller.
     *
     * @param  \LaraGram\Listening\Listen  $listen
     * @param  mixed  $controller
     * @param  string  $method
     * @return array
     */
    protected function resolveParameters(Listen $listen, $controller, $method)
    {
        return $this->resolveClassMethodDependencies(
            $listen->parametersWithoutNulls(), $controller, $method
        );
    }

    /**
     * Get the middleware for the controller instance.
     *
     * @param  \LaraGram\Listening\Controller  $controller
     * @param  string  $method
     * @return array
     */
    public function getMiddleware($controller, $method)
    {
        if (! method_exists($controller, 'getMiddleware')) {
            return [];
        }

        return (new Collection($controller->getMiddleware()))->reject(function ($data) use ($method) {
            return static::methodExcludedByOptions($method, $data['options']);
        })->pluck('middleware')->all();
    }
}
