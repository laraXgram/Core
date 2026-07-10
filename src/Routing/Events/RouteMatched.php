<?php

namespace LaraGram\Routing\Events;

class RouteMatched
{
    /**
     * Create a new event instance.
     *
     * @param  \LaraGram\Routing\Route  $route  The route instance.
     * @param  \LaraGram\Http\Request  $request  The request instance.
     */
    public function __construct(
        public $route,
        public $request,
    ) {
    }
}
