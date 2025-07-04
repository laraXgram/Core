<?php

namespace LaraGram\Listening\Events;

class PreparingResponse
{
    /**
     * Create a new event instance.
     *
     * @param  \LaraGram\Request\Request  $request  The request instance.
     * @param  mixed  $response  The response instance.
     * @return void
     */
    public function __construct(
        public $request,
        public $response,
    ) {
    }
}
