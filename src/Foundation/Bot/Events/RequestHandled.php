<?php

namespace LaraGram\Foundation\Bot\Events;

class RequestHandled
{
    /**
     * The request instance.
     *
     * @var \LaraGram\Request\Request
     */
    public $request;

    /**
     * The response instance.
     *
     * @var \LaraGram\Request\Response
     */
    public $response;

    /**
     * Create a new event instance.
     *
     * @param  \LaraGram\Request\Request  $request
     * @param  \LaraGram\Request\Response  $response
     * @return void
     */
    public function __construct($request, $response)
    {
        $this->request = $request;
        $this->response = $response;
    }
}
