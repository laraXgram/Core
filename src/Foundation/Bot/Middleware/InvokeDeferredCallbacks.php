<?php

namespace LaraGram\Foundation\Bot\Middleware;

use Closure;
use LaraGram\Container\Container;
use LaraGram\Request\Request;
use LaraGram\Support\Defer\DeferredCallbackCollection;
use LaraGram\Request\Response;

class InvokeDeferredCallbacks
{
    /**
     * Handle the incoming request.
     *
     * @param  \LaraGram\Request\Request  $request
     * @param  \Closure  $next
     * @return \LaraGram\Request\Response
     */
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    /**
     * Invoke the deferred callbacks.
     *
     * @param  \LaraGram\Request\Request  $request
     * @param  \LaraGram\Request\Response  $response
     * @return void
     */
    public function terminate(Request $request, Response $response)
    {
        Container::getInstance()
            ->make(DeferredCallbackCollection::class)
            ->invokeWhen(fn ($callback) => $callback->always);
    }
}
