<?php

namespace LaraGram\Http\VarDumper\Caster;

use LaraGram\Http\VarDumper\Cloner\Stub;

class EnumStub extends Stub
{
    public function __construct(
        array $values,
        public bool $dumpKeys = true,
    ) {
        $this->value = $values;
    }
}
