<?php

namespace LaraGram\Listening\Events;

class Listening
{
    /**
     * Create a new event instance.
     *
     * @param  \LaraGram\Request\Request  $request  The request instance.
     * @return void
     */
    public function __construct(
        public $request,
    ) {
    }
}
