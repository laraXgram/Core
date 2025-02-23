<?php

namespace LaraGram\Console\Events;

class CommanderStarting
{
    /**
     * The Commander application instance.
     *
     * @var \LaraGram\Console\Application
     */
    public $commander;

    /**
     * Create a new event instance.
     *
     * @param  \LaraGram\Console\Application  $commander
     * @return void
     */
    public function __construct($commander)
    {
        $this->commander = $commander;
    }
}
