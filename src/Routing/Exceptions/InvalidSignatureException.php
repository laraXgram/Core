<?php

namespace LaraGram\Routing\Exceptions;

use LaraGram\Foundation\Http\Exceptions\HttpException;

class InvalidSignatureException extends HttpException
{
    /**
     * Create a new exception instance.
     */
    public function __construct()
    {
        parent::__construct(403, 'Invalid signature.');
    }
}
