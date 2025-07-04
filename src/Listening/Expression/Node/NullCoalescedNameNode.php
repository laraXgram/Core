<?php

namespace LaraGram\Listening\Expression\Node;

use Symfony\Component\ExpressionLanguage\Compiler;

class NullCoalescedNameNode extends Node
{
    public function __construct(string $name)
    {
        parent::__construct(
            [],
            ['name' => $name]
        );
    }

    public function compile(Compiler $compiler): void
    {
        $compiler->raw('$'.$this->attributes['name'].' ?? null');
    }

    public function evaluate(array $functions, array $values): null
    {
        return null;
    }

    public function toArray(): array
    {
        return [$this->attributes['name'].' ?? null'];
    }
}
