<?php

namespace LaraGram\Routing\Exceptions;

class RouteCircularReferenceException extends RuntimeException
{
    public function __construct(string $routeId, array $path)
    {
        parent::__construct(\sprintf('Circular reference detected for route "%s", path: "%s".', $routeId, implode(' -> ', $path)));
    }
}
