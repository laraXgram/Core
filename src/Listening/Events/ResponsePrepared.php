<?php

namespace LaraGram\Listening\Events;

class ResponsePrepared
{
    /**
     * Create a new event instance.
     *
     * @param  \LaraGram\Request\Request  $request  The request instance.
     * @param  \LaraGram\Request\Response  $response  The response instance.
     * @return void
     */
    public function __construct(
        public $request,
        public $response,
    ) {
    }
}
