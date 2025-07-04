<?php

namespace LaraGram\Support\Facades;

/**
 * @method static bool set(string $key, int $ttl = null)
 * @method static mixed get()
 * @method static mixed pull()
 * @method static bool forget()
 * @method static bool hasStep()
 * @method static bool hasNotStep()
 *
 * @see \LaraGram\Cache\Step
 */
class Step extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return \LaraGram\Cache\Step::class;
    }
}
