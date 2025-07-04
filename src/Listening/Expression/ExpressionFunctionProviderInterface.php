<?php

namespace LaraGram\Listening\Expression;

interface ExpressionFunctionProviderInterface
{
    /**
     * @return ExpressionFunction[]
     */
    public function getFunctions(): array;
}
