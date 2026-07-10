<?php

namespace LaraGram\Listening\Expression\Node;

use LaraGram\Listening\Expression\Compiler;

class NameNode extends Node
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
        $compiler->raw('$'.$this->attributes['name']);
    }

    public function evaluate(array $functions, array $values): mixed
    {
        return $values[$this->attributes['name']];
    }

    public function toArray(): array
    {
        return [$this->attributes['name']];
    }
}
