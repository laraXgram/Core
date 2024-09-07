<?php

namespace LaraGram\Contracts\Container;

use Closure;
use LaraGram\Container\ContextualBindingBuilder;

interface Container
{
    public function bind($abstract, $concrete = null, $shared = false);

    public function singleton($abstract, $concrete = null);

    public function make($abstract);

    public function alias($abstract, $alias);

    public function resolving($abstract, Closure $callback);

    public function when($concrete);
}