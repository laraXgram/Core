<?php

namespace LaraGram\Listening\Expression\Node;

use Symfony\Component\ExpressionLanguage\Compiler;

class ArgumentsNode extends ArrayNode
{
    public function compile(Compiler $compiler): void
    {
        $this->compileArguments($compiler, false);
    }

    public function toArray(): array
    {
        $array = [];

        foreach ($this->getKeyValuePairs() as $pair) {
            $array[] = $pair['value'];
            $array[] = ', ';
        }
        array_pop($array);

        return $array;
    }
}
