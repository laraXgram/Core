<?php

namespace LaraGram\Listening\Expression\Node;

use Symfony\Component\ExpressionLanguage\Compiler;

class UnaryNode extends Node
{
    private const OPERATORS = [
        '!' => '!',
        'not' => '!',
        '+' => '+',
        '-' => '-',
        '~' => '~',
    ];

    public function __construct(string $operator, Node $node)
    {
        parent::__construct(
            ['node' => $node],
            ['operator' => $operator]
        );
    }

    public function compile(Compiler $compiler): void
    {
        $compiler
            ->raw('(')
            ->raw(self::OPERATORS[$this->attributes['operator']])
            ->compile($this->nodes['node'])
            ->raw(')')
        ;
    }

    public function evaluate(array $functions, array $values): mixed
    {
        $value = $this->nodes['node']->evaluate($functions, $values);

        return match ($this->attributes['operator']) {
            'not',
            '!' => !$value,
            '-' => -$value,
            '~' => ~$value,
            default => $value,
        };
    }

    public function toArray(): array
    {
        return ['(', $this->attributes['operator'].' ', $this->nodes['node'], ')'];
    }
}
