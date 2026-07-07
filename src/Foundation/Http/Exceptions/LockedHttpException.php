<?php

namespace LaraGram\Foundation\Http\Exceptions;

class LockedHttpException extends HttpException
{
    public function __construct(string $message = '', ?\Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct(423, $message, $previous, $headers, $code);
    }
}
