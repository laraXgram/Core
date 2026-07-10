<?php

namespace LaraGram\Console\Events;

use LaraGram\Console\Application;

class CommanderStarting
{
    /**
     * Create a new event instance.
     *
     * @param  \LaraGram\Console\Application  $commander  The Commander application instance.
     */
    public function __construct(
        public Application $commander,
    ) {
    }
}
