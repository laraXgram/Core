<?php

namespace LaraGram\Routing\Matching;

use LaraGram\Http\Request;
use LaraGram\Routing\Route;

class MethodValidator implements ValidatorInterface
{
    /**
     * Validate a given rule against a route and request.
     *
     * @param  \LaraGram\Routing\Route  $route
     * @param  \LaraGram\Http\Request  $request
     * @return bool
     */
    public function matches(Route $route, Request $request)
    {
        return in_array($request->getMethod(), $route->methods());
    }
}
