<?php

namespace LaraGram\Http\VarDumper\Caster;

class CutArrayStub extends CutStub
{
    public array $preservedSubset;

    public function __construct(array $value, array $preservedKeys)
    {
        parent::__construct($value);

        $this->preservedSubset = array_intersect_key($value, array_flip($preservedKeys));
        $this->cut -= \count($this->preservedSubset);
    }
}
