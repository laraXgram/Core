<?php

namespace LaraGram\Routing;

interface RouteCompilerInterface
{
    /**
     * Compiles the current route instance.
     *
     * @throws \LogicException If the Route cannot be compiled because the
     *                         path or host pattern is invalid
     */
    public static function compile(BaseRoute $route): CompiledRoute;
}
