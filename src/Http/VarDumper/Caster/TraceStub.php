<?php

namespace LaraGram\Http\VarDumper\Caster;

use LaraGram\Http\VarDumper\Cloner\Stub;

class TraceStub extends Stub
{
    public function __construct(
        array $trace,
        public bool $keepArgs = true,
        public int $sliceOffset = 0,
        public ?int $sliceLength = null,
        public int $numberingOffset = 0,
    ) {
        $this->value = $trace;
    }
}
