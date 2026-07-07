<?php

namespace LaraGram\Http\VarDumper\Caster;

class FrameStub extends EnumStub
{
    public function __construct(
        array $frame,
        public bool $keepArgs = true,
        public bool $inTraceStub = false,
    ) {
        parent::__construct($frame);
    }
}
