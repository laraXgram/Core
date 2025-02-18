<?php

namespace LaraGram\Contracts\Cache;

interface Factory
{
    /**
     * Get a cache store instance by name.
     *
     * @param  string|null  $name
     * @return \LaraGram\Contracts\Cache\Repository
     */
    public function store($name = null);
}
