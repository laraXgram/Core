<?php

namespace LaraGram\Listening\Middleware;

use Closure;

class Reply
{
    /**
     * Handle an incoming request.
     *
     * @param  \LaraGram\Request\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $bool)
    {
        if (($bool || $bool == 'true') && $request->isReply()) {
           return $next($request);
        }

        return false;
    }
}
