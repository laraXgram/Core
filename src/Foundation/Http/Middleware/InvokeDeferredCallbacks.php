<?php

namespace LaraGram\Foundation\Http\Middleware;

use Closure;
use LaraGram\Container\Container;
use LaraGram\Http\Request;
use LaraGram\Support\Defer\DeferredCallbackCollection;
use LaraGram\Http\BaseResponse;

class InvokeDeferredCallbacks
{
    /**
     * Handle the incoming request.
     *
     * @param  \LaraGram\Http\Request  $request
     * @param  \Closure  $next
     * @return \LaraGram\Http\BaseResponse
     */
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    /**
     * Invoke the deferred callbacks.
     *
     * @param  \LaraGram\Http\Request  $request
     * @param  \LaraGram\Http\BaseResponse  $response
     * @return void
     */
    public function terminate(Request $request, BaseResponse $response)
    {
        Container::getInstance()
            ->make(DeferredCallbackCollection::class)
            ->invokeWhen(fn ($callback) => $response->getStatusCode() < 400 || $callback->always);
    }
}
