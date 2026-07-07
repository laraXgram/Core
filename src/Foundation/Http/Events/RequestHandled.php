<?php

namespace LaraGram\Foundation\Http\Events;

class RequestHandled
{
    /**
     * The request instance.
     *
     * @var \LaraGram\Http\Request
     */
    public $request;

    /**
     * The response instance.
     *
     * @var \LaraGram\Http\Response
     */
    public $response;

    /**
     * Create a new event instance.
     *
     * @param  \LaraGram\Http\Request  $request
     * @param  \LaraGram\Http\Response  $response
     */
    public function __construct($request, $response)
    {
        $this->request = $request;
        $this->response = $response;
    }
}
