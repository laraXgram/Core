<?php

namespace LaraGram\Listening\Exceptions;

class ListenCircularReferenceException extends \RuntimeException
{
    public function __construct(string $listenId, array $pattern)
    {
        parent::__construct(\sprintf('Circular reference detected for listen "%s", pattern: "%s".', $listenId, implode(' -> ', $path)));
    }
}
