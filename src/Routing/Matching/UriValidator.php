<?php

namespace LaraGram\Routing\Matching;

use LaraGram\Http\Request;
use LaraGram\Routing\Route;

class UriValidator implements ValidatorInterface
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
        $path = rtrim($request->getPathInfo(), '/') ?: '/';

        return preg_match($route->getCompiled()->getRegex(), rawurldecode($path));
    }
}
