<?php

namespace LaraGram\Contracts\Broadcasting;

interface Factory
{
    /**
     * Get a broadcaster implementation by name.
     *
     * @param  string|null  $name
     * @return \LaraGram\Contracts\Broadcasting\Broadcaster
     */
    public function connection($name = null);
}
