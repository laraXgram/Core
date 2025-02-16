<?php

namespace LaraGram\Contracts\Database\Query;

use LaraGram\Database\Grammar;

interface Expression
{
    /**
     * Get the value of the expression.
     *
     * @param  \LaraGram\Database\Grammar  $grammar
     * @return string|int|float
     */
    public function getValue(Grammar $grammar);
}
