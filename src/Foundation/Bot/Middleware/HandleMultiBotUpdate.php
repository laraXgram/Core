<?php

namespace LaraGram\Foundation\Bot\Middleware;

use Closure;
use LaraGram\Request\ConnectionResolver;
use LaraGram\Request\Request;

/**
 * @deprecated The bot kernel binds the connection of every update before listen
 *             matching. This middleware only covers requests that bypass it.
 */
class HandleMultiBotUpdate
{
    /**
     * Create a new middleware instance.
     *
     * @param  \LaraGram\Request\ConnectionResolver  $resolver
     * @return void
     */
    public function __construct(protected ConnectionResolver $resolver)
    {
        //
    }

    /**
     * Handle the incoming request.
     *
     * @param  \LaraGram\Request\Request  $request
     * @param  \Closure  $next
     * @return \LaraGram\Request\Response
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->getBoundConnection() === null &&
            ! is_null($connection = $this->resolver->resolve($request))) {
            $request->useConnection($connection);
        }

        return $next($request);
    }
}
