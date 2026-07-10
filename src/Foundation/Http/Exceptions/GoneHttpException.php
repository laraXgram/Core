<?php

namespace LaraGram\Foundation\Http\Exceptions;

class GoneHttpException extends HttpException
{
    public function __construct(string $message = '', ?\Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct(410, $message, $previous, $headers, $code);
    }
}
