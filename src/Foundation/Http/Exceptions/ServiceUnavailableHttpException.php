<?php

namespace LaraGram\Foundation\Http\Exceptions;

class ServiceUnavailableHttpException extends HttpException
{
    /**
     * @param int|string|null $retryAfter The number of seconds or HTTP-date after which the request may be retried
     */
    public function __construct(int|string|null $retryAfter = null, string $message = '', ?\Throwable $previous = null, int $code = 0, array $headers = [])
    {
        if ($retryAfter) {
            $headers['Retry-After'] = $retryAfter;
        }

        parent::__construct(503, $message, $previous, $headers, $code);
    }
}
