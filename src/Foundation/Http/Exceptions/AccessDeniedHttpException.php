<?php

namespace LaraGram\Foundation\Http\Exceptions;

class AccessDeniedHttpException extends HttpException
{
    public function __construct(string $message = '', ?\Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct(403, $message, $previous, $headers, $code);
    }
}
