<?php

namespace LaraGram\Routing\Matcher\Dumper;

use LaraGram\Routing\BaseRouteCollection;

interface MatcherDumperInterface
{
    /**
     * Dumps a set of routes to a string representation of executable code
     * that can then be used to match a request against these routes.
     */
    public function dump(array $options = []): string;

    /**
     * Gets the routes to dump.
     */
    public function getRoutes(): BaseRouteCollection;
}
