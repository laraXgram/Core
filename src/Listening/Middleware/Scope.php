<?php

namespace LaraGram\Listening\Middleware;

use Closure;

class Scope
{
    /**
     * Handle an incoming request.
     *
     * @param  \LaraGram\Request\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $scope)
    {
        $scope = explode(',', $scope);

        if (in_array($request->scope(), $scope)){
            return $next($request);
        }

        return false;
    }
}
