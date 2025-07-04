<?php

namespace LaraGram\Listening;

use LaraGram\Container\Container;
use LaraGram\Listening\Contracts\CallableDispatcher as CallableDispatcherContract;
use ReflectionFunction;

class CallableDispatcher implements CallableDispatcherContract
{
    use ResolvesListenDependencies;

    /**
     * The container instance.
     *
     * @var \LaraGram\Container\Container
     */
    protected $container;

    /**
     * Create a new callable dispatcher instance.
     *
     * @param  \LaraGram\Container\Container  $container
     * @return void
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Dispatch a request to a given callable.
     *
     * @param  \LaraGram\Listening\Listen  $listen
     * @param  callable  $callable
     * @return mixed
     */
    public function dispatch(Listen $listen, $callable)
    {
        return $callable(...array_values($this->resolveParameters($listen, $callable)));
    }

    /**
     * Resolve the parameters for the callable.
     *
     * @param  \LaraGram\Listening\Listen  $listen
     * @param  callable  $callable
     * @return array
     */
    protected function resolveParameters(Listen $listen, $callable)
    {
        return $this->resolveMethodDependencies($listen->parametersWithoutNulls(), new ReflectionFunction($callable));
    }
}
