<?php

namespace LaraGram\Routing\Events;

class Routing
{
    /**
     * Create a new event instance.
     *
     * @param  \LaraGram\Http\Request  $request  The request instance.
     */
    public function __construct(
        public $request,
    ) {
    }
}
