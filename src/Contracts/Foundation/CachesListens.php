<?php

namespace LaraGram\Contracts\Foundation;

interface CachesListens
{
    /**
     * Determine if the application listens are cached.
     *
     * @return bool
     */
    public function listensAreCached();

    /**
     * Get the path to the listens cache file.
     *
     * @return string
     */
    public function getCachedListensPath();
}
