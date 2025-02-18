<?php

namespace LaraGram\Contracts\Filesystem;

interface Factory
{
    /**
     * Get a filesystem implementation.
     *
     * @param  string|null  $name
     * @return \LaraGram\Contracts\Filesystem\Filesystem
     */
    public function disk($name = null);
}
