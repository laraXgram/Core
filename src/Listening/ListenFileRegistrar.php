<?php

namespace LaraGram\Listening;

class ListenFileRegistrar
{
    /**
     * The listener instance.
     *
     * @var \LaraGram\Listening\Listener
     */
    protected $listener;

    /**
     * Create a new listener file registrar instance.
     *
     * @param  \LaraGram\Listening\Listener  $listens
     * @return void
     */
    public function __construct(Listener $listener)
    {
        $this->listener = $listener;
    }

    /**
     * Require the given listens file.
     *
     * @param  string  $listens
     * @return void
     */
    public function register($listens)
    {
        $listener = $this->listener;

        require $listens;
    }
}
