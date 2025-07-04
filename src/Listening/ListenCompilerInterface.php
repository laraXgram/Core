<?php

namespace LaraGram\Listening;

interface ListenCompilerInterface
{
    /**
     * Compiles the current listen instance.
     *
     * @throws \LogicException If the Listen cannot be compiled because the
     *                         pattern is invalid
     */
    public static function compile(BaseListen $listen): CompiledListen;
}
