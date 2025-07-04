<?php

namespace LaraGram\Request\Exceptions;

use Throwable;

class ThrottleRequestsException extends \RuntimeException
{
    /**
     * Create a new throttle requests exception instance.
     *
     * @param  string $message
     * @param  int $code
     * @param  \Throwable|null  $previous
     * @return void
     */
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
