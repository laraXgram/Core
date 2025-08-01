<?php

namespace LaraGram\Listening\Expression\Node;

use Symfony\Component\ExpressionLanguage\Compiler;

class NullCoalesceNode extends Node
{
    public function __construct(Node $expr1, Node $expr2)
    {
        parent::__construct(['expr1' => $expr1, 'expr2' => $expr2]);
    }

    public function compile(Compiler $compiler): void
    {
        $compiler
            ->raw('((')
            ->compile($this->nodes['expr1'])
            ->raw(') ?? (')
            ->compile($this->nodes['expr2'])
            ->raw('))')
        ;
    }

    public function evaluate(array $functions, array $values): mixed
    {
        if ($this->nodes['expr1'] instanceof GetAttrNode) {
            $this->addNullCoalesceAttributeToGetAttrNodes($this->nodes['expr1']);
        }

        return $this->nodes['expr1']->evaluate($functions, $values) ?? $this->nodes['expr2']->evaluate($functions, $values);
    }

    public function toArray(): array
    {
        return ['(', $this->nodes['expr1'], ') ?? (', $this->nodes['expr2'], ')'];
    }

    private function addNullCoalesceAttributeToGetAttrNodes(Node $node): void
    {
        if (!$node instanceof GetAttrNode) {
            return;
        }

        $node->attributes['is_null_coalesce'] = true;

        foreach ($node->nodes as $node) {
            $this->addNullCoalesceAttributeToGetAttrNodes($node);
        }
    }
}
