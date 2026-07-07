<?php

namespace LaraGram\Routing\Events;

class PreparingResponse
{
    /**
     * Create a new event instance.
     *
     * @param  \LaraGram\Http\BaseRequest  $request  The request instance.
     * @param  mixed  $response  The response instance.
     */
    public function __construct(
        public $request,
        public $response,
    ) {
    }
}
