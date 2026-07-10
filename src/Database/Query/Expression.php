<?php

namespace LaraGram\Database\Query;

use LaraGram\Contracts\Database\Query\Expression as ExpressionContract;
use LaraGram\Database\Grammar;

/**
 * @template TValue of literal-string|int|float
 */
class Expression implements ExpressionContract
{
    /**
     * Create a new raw query expression.
     *
     * @param  TValue  $value
     */
    public function __construct(
        protected $value,
    ) {
    }

    /**
     * Get the value of the expression.
     *
     * @param  \LaraGram\Database\Grammar  $grammar
     * @return TValue
     */
    public function getValue(Grammar $grammar)
    {
        return $this->value;
    }
}
