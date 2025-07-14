<?php

namespace LaraGram\Listening\Middleware;

use Closure;
use LaraGram\Contracts\Listening\Registrar;
use LaraGram\Database\Eloquent\ModelNotFoundException;

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
        if ($bool) {
            return $request->isReply()
                ? $next($request)
                : false;
        }

        return $request->isReply()
            ? false
            : $next($request);
    }
}
