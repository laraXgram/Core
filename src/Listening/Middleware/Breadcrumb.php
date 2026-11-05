<?php

namespace LaraGram\Listening\Middleware;

use Closure;
use LaraGram\Listening\Navigation\Navigator;

class Breadcrumb
{
    /**
     * Create a new middleware instance.
     *
     * @param  \LaraGram\Listening\Navigation\Navigator  $navigator
     * @return void
     */
    public function __construct(
        protected Navigator $navigator,
    ) {
    }

    /**
     * Record the listen being run as a step of the user's trail.
     *
     * @param  \LaraGram\Request\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $listen = $request->listen();
        $breadcrumb = $listen?->getAction('breadcrumb') ?? [];

        $this->navigator->push($breadcrumb['name'] ?? null, $breadcrumb['title'] ?? null, $listen);

        return $next($request);
    }
}
