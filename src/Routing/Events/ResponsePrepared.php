<?php

namespace LaraGram\Routing\Events;

class ResponsePrepared
{
    /**
     * Create a new event instance.
     *
     * @param  \LaraGram\Http\BaseRequest  $request  The request instance.
     * @param  \LaraGram\Http\BaseResponse  $response  The response instance.
     */
    public function __construct(
        public $request,
        public $response,
    ) {
    }
}
