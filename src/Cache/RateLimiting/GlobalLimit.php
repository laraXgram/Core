<?php

namespace LaraGram\Cache\RateLimiting;

class GlobalLimit extends Limit
{
    /**
     * Create a new limit instance.
     *
     * @param  int  $maxAttempts
     * @param  int  $decaySeconds
     * @return void
     */
    public function __construct(int $maxAttempts, int $decaySeconds = 60)
    {
        parent::__construct('', $maxAttempts, $decaySeconds);
    }
}
