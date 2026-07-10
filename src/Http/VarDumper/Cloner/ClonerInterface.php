<?php

namespace LaraGram\Http\VarDumper\Cloner;

interface ClonerInterface
{
    /**
     * Clones a PHP variable.
     */
    public function cloneVar(mixed $var): Data;
}
