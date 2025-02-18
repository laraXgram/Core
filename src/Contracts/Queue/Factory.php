<?php

namespace LaraGram\Contracts\Queue;

interface Factory
{
    /**
     * Resolve a queue connection instance.
     *
     * @param  string|null  $name
     * @return \LaraGram\Contracts\Queue\Queue
     */
    public function connection($name = null);
}
