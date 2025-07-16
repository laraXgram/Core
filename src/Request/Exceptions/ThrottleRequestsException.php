<?php

namespace LaraGram\Request\Exceptions;

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
    public function __construct(string $message = '')
    {
        parent::__construct($message);
    }
}
