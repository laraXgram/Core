<?php

namespace LaraGram\Http\Files\Exceptions;

class UnexpectedTypeException extends FileException
{
    public function __construct(mixed $value, string $expectedType)
    {
        parent::__construct(\sprintf('Expected argument of type %s, %s given', $expectedType, get_debug_type($value)));
    }
}
