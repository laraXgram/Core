<?php

namespace LaraGram\Http\VarDumper\Caster;

use LaraGram\Http\VarDumper\Cloner\Stub;

class ScalarStub extends Stub
{
    public function __construct(mixed $value)
    {
        $this->value = $value;
    }
}
