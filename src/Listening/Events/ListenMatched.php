<?php

namespace LaraGram\Listening\Events;

class ListenMatched
{
    /**
     * Create a new event instance.
     *
     * @param  \LaraGram\Listening\Listen  $listen  The listen instance.
     * @param  \LaraGram\Request\Request  $request  The request instance.
     * @return void
     */
    public function __construct(
        public $listen,
        public $request,
    ) {
    }
}
