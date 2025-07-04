<?php

namespace LaraGram\Listening\Expression\Node;

use Symfony\Component\ExpressionLanguage\Compiler;

class ConstantNode extends Node
{
    public function __construct(
        mixed $value,
        private bool $isIdentifier = false,
        public readonly bool $isNullSafe = false,
    ) {
        parent::__construct(
            [],
            ['value' => $value]
        );
    }

    public function compile(Compiler $compiler): void
    {
        $compiler->repr($this->attributes['value']);
    }

    public function evaluate(array $functions, array $values): mixed
    {
        return $this->attributes['value'];
    }

    public function toArray(): array
    {
        $array = [];
        $value = $this->attributes['value'];

        if ($this->isIdentifier) {
            $array[] = $value;
        } elseif (true === $value) {
            $array[] = 'true';
        } elseif (false === $value) {
            $array[] = 'false';
        } elseif (null === $value) {
            $array[] = 'null';
        } elseif (is_numeric($value)) {
            $array[] = $value;
        } elseif (!\is_array($value)) {
            $array[] = $this->dumpString($value);
        } elseif ($this->isHash($value)) {
            foreach ($value as $k => $v) {
                $array[] = ', ';
                $array[] = new self($k);
                $array[] = ': ';
                $array[] = new self($v);
            }
            $array[0] = '{';
            $array[] = '}';
        } else {
            foreach ($value as $v) {
                $array[] = ', ';
                $array[] = new self($v);
            }
            $array[0] = '[';
            $array[] = ']';
        }

        return $array;
    }
}
