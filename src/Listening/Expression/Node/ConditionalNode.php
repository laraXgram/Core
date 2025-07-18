<?php

namespace LaraGram\Listening\Expression\Node;

use Symfony\Component\ExpressionLanguage\Compiler;

class ConditionalNode extends Node
{
    public function __construct(Node $expr1, Node $expr2, Node $expr3)
    {
        parent::__construct(
            ['expr1' => $expr1, 'expr2' => $expr2, 'expr3' => $expr3]
        );
    }

    public function compile(Compiler $compiler): void
    {
        $compiler
            ->raw('((')
            ->compile($this->nodes['expr1'])
            ->raw(') ? (')
            ->compile($this->nodes['expr2'])
            ->raw(') : (')
            ->compile($this->nodes['expr3'])
            ->raw('))')
        ;
    }

    public function evaluate(array $functions, array $values): mixed
    {
        if ($this->nodes['expr1']->evaluate($functions, $values)) {
            return $this->nodes['expr2']->evaluate($functions, $values);
        }

        return $this->nodes['expr3']->evaluate($functions, $values);
    }

    public function toArray(): array
    {
        return ['(', $this->nodes['expr1'], ' ? ', $this->nodes['expr2'], ' : ', $this->nodes['expr3'], ')'];
    }
}
