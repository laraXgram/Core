<?php

namespace LaraGram\Foundation\Bot\Events;

class DuplicateUpdateSkipped
{
    /**
     * The request instance.
     *
     * @var \LaraGram\Request\Request
     */
    public $request;

    /**
     * Create a new event instance.
     *
     * @param  \LaraGram\Request\Request  $request
     * @return void
     */
    public function __construct($request)
    {
        $this->request = $request;
    }
}
