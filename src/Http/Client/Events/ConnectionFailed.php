<?php

namespace LaraGram\Http\Client\Events;

use LaraGram\Http\Client\ConnectionException;
use LaraGram\Http\Client\Request;

class ConnectionFailed
{
    /**
     * The request instance.
     *
     * @var \LaraGram\Http\Client\Request
     */
    public $request;

    /**
     * The exception instance.
     *
     * @var \LaraGram\Http\Client\ConnectionException
     */
    public $exception;

    /**
     * Create a new event instance.
     *
     * @param  \LaraGram\Http\Client\Request  $request
     * @param  \LaraGram\Http\Client\ConnectionException  $exception
     */
    public function __construct(Request $request, ConnectionException $exception)
    {
        $this->request = $request;
        $this->exception = $exception;
    }
}
