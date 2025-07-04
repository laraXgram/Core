<?php

namespace LaraGram\Listening\Expression;

use LaraGram\Listening\Expression\Node\Node;

class SerializedParsedExpression extends ParsedExpression
{
    /**
     * @param string $expression An expression
     * @param string $nodes      The serialized nodes for the expression
     */
    public function __construct(
        string $expression,
        private string $nodes,
    ) {
        $this->expression = $expression;
    }

    public function getNodes(): Node
    {
        return unserialize($this->nodes);
    }
}
