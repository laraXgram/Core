<?php

namespace LaraGram\Contracts\Events;

interface ShouldBeDiscovered
{
    /**
     * Determine if the listener should be registered during event discovery.
     */
    public static function shouldBeDiscovered(): bool;
}
