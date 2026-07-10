<?php

namespace LaraGram\Http\VarDumper\Caster;

class VirtualStub extends ConstStub
{
    public function __construct(\ReflectionProperty $property)
    {
        parent::__construct('~'.($property->hasType() ? ' '.$property->getType() : ''), 'Virtual property');
        $this->attr['virtual'] = true;
    }
}
