<?php

namespace LaraGram\Listening\Controllers;

interface HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     *
     * @return array<int,\LaraGram\Listening\Controllers\Middleware|\Closure|string>
     */
    public static function middleware();
}
