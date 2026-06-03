<?php

namespace LaraGram\Foundation\Bot\Middleware;

use Closure;
use LaraGram\Request\Request;

class HandleMultiBotUpdate
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
        if (config('bot.default') === 'auto') {
            foreach (config('bot.connections') as $name => $config) {
                if (($config['secret_token'] ?? null) === $request->secretToken()) {
                    Request::setDefaultConnection($name);
                    return $next($request);
                }
            }
        }

        return $next($request);
    }
}
