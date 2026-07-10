<?php

namespace LaraGram\Http\Exceptions;

use LaraGram\Foundation\Http\Exceptions\HttpException;

class MalformedUrlException extends HttpException
{
    /**
     * Create a new exception instance.
     */
    public function __construct()
    {
        parent::__construct(400, 'Malformed URL.');
    }
}
