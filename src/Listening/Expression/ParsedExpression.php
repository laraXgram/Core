<?php

namespace LaraGram\Listening\Expression;

use LaraGram\Listening\Expression\Node\Node;

class ParsedExpression extends Expression
{
    public function __construct(
        string $expression,
        private Node $nodes,
    ) {
        parent::__construct($expression);
    }

    public function getNodes(): Node
    {
        return $this->nodes;
    }
}
