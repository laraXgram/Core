<?php

namespace LaraGram\Http\Client\Events;

use LaraGram\Http\Client\Request;
use LaraGram\Http\Client\Response;

class ResponseReceived
{
    /**
     * The request instance.
     *
     * @var \LaraGram\Http\Client\Request
     */
    public $request;

    /**
     * The response instance.
     *
     * @var \LaraGram\Http\Client\Response
     */
    public $response;

    /**
     * Create a new event instance.
     *
     * @param  \LaraGram\Http\Client\Request  $request
     * @param  \LaraGram\Http\Client\Response  $response
     */
    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }
}
