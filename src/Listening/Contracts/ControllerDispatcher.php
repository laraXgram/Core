<?php

namespace LaraGram\Listening\Contracts;

use LaraGram\Listening\Listen;

interface ControllerDispatcher
{
    /**
     * Dispatch a request to a given controller and method.
     *
     * @param  \LaraGram\Listening\Listen  $listen
     * @param  mixed  $controller
     * @param  string  $method
     * @return mixed
     */
    public function dispatch(Listen $listen, $controller, $method);

    /**
     * Get the middleware for the controller instance.
     *
     * @param  \LaraGram\Listening\Controller  $controller
     * @param  string  $method
     * @return array
     */
    public function getMiddleware($controller, $method);
}
