<?php

namespace LaraGram\Console\Exception;

final class RunCommandFailedException extends RuntimeException
{
    public function __construct(\Throwable|string $exception, public readonly RunCommandContext $context)
    {
        parent::__construct(
            $exception instanceof \Throwable ? $exception->getMessage() : $exception,
            $exception instanceof \Throwable ? $exception->getCode() : 0,
            $exception instanceof \Throwable ? $exception : null,
        );
    }
}
