<?php

namespace LaraGram\Http\Client\Events;

use LaraGram\Http\Client\Request;

class RequestSending
{
    /**
     * The request instance.
     *
     * @var \LaraGram\Http\Client\Request
     */
    public $request;

    /**
     * Create a new event instance.
     *
     * @param  \LaraGram\Http\Client\Request  $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
}
