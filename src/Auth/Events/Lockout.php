<?php

namespace LaraGram\Auth\Events;

use LaraGram\Http\Request;

class Lockout
{
    /**
     * The throttled request.
     *
     * @var \LaraGram\Http\Request
     */
    public $request;

    /**
     * Create a new event instance.
     *
     * @param  \LaraGram\Http\Request  $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
}
