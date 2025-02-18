<?php

namespace LaraGram\Contracts\Redis;

interface Factory
{
    /**
     * Get a Redis connection by name.
     *
     * @param  string|null  $name
     * @return \LaraGram\Redis\Connections\Connection
     */
    public function connection($name = null);
}
