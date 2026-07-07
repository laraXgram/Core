<?php

namespace LaraGram\Routing\Matching;

use LaraGram\Http\Request;
use LaraGram\Routing\Route;

interface ValidatorInterface
{
    /**
     * Validate a given rule against a route and request.
     *
     * @param  \LaraGram\Routing\Route  $route
     * @param  \LaraGram\Http\Request  $request
     * @return bool
     */
    public function matches(Route $route, Request $request);
}
