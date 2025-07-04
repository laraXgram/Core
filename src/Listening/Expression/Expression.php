<?php

namespace LaraGram\Listening\Expression;

class Expression
{
    public function __construct(
        protected string $expression,
    ) {
    }

    /**
     * Gets the expression.
     */
    public function __toString(): string
    {
        return $this->expression;
    }
}
