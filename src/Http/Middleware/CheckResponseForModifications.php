<?php

namespace LaraGram\Http\Middleware;

use Closure;
use LaraGram\Http\BaseResponse;

class CheckResponseForModifications
{
    /**
     * Handle an incoming request.
     *
     * @param  \LaraGram\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if ($response instanceof BaseResponse) {
            $response->isNotModified($request);
        }

        return $response;
    }
}
