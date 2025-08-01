<?php

namespace LaraGram\Listening\Expression\Node;

use Symfony\Component\ExpressionLanguage\Compiler;

class FunctionNode extends Node
{
    public function __construct(string $name, Node $arguments)
    {
        parent::__construct(
            ['arguments' => $arguments],
            ['name' => $name]
        );
    }

    public function compile(Compiler $compiler): void
    {
        $arguments = [];
        foreach ($this->nodes['arguments']->nodes as $node) {
            $arguments[] = $compiler->subcompile($node);
        }

        $function = $compiler->getFunction($this->attributes['name']);

        $compiler->raw($function['compiler'](...$arguments));
    }

    public function evaluate(array $functions, array $values): mixed
    {
        $arguments = [$values];
        foreach ($this->nodes['arguments']->nodes as $node) {
            $arguments[] = $node->evaluate($functions, $values);
        }

        return $functions[$this->attributes['name']]['evaluator'](...$arguments);
    }

    public function toArray(): array
    {
        $array = [];
        $array[] = $this->attributes['name'];

        foreach ($this->nodes['arguments']->nodes as $node) {
            $array[] = ', ';
            $array[] = $node;
        }
        $array[1] = '(';
        $array[] = ')';

        return $array;
    }
}
