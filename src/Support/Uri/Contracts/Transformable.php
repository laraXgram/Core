<?php

namespace LaraGram\Support\Uri\Contracts;

interface Transformable
{
    /**
     * Apply a transformation to this instance and return a new instance.
     *
     * This method MUST retain the state of the current instance, and return
     * a new instance of the same type.
     *
     * @param callable(static): static $callback
     */
    public function transform(callable $callback): static;
}
