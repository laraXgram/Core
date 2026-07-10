<?php

namespace LaraGram\Routing\Matcher\Dumper;

use LaraGram\Routing\BaseRouteCollection;

abstract class MatcherDumper implements MatcherDumperInterface
{
    public function __construct(
        private BaseRouteCollection $routes,
    ) {
    }

    public function getRoutes(): BaseRouteCollection
    {
        return $this->routes;
    }
}
