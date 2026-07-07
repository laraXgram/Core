<?php

namespace LaraGram\Foundation\Http\Exceptions;

class MethodNotAllowedHttpException extends HttpException
{
    /**
     * @param string[] $allow An array of allowed methods
     */
    public function __construct(array $allow, string $message = '', ?\Throwable $previous = null, int $code = 0, array $headers = [])
    {
        $headers['Allow'] = strtoupper(implode(', ', $allow));

        parent::__construct(405, $message, $previous, $headers, $code);
    }
}
