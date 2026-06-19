<?php

namespace LaraGram\Support\Facades;

/**
 * @method static bool set(string $key, int $ttl = null)
 * @method static mixed get()
 * @method static mixed pull()
 * @method static bool forget()
 * @method static bool hasStep()
 * @method static bool hasNotStep()
 * @method static bool is(string $key)
 * @method static bool isNot(string $key)
 * @method static array|null getSequence()
 * @method static void startSequence(array $sequence)
 * @method static void endSequence()
 * @method static void next()
 * @method static void previous()
 * @method static string|null current()
 * @method static bool isFirst()
 * @method static bool isLast()
 * @method static \LaraGram\Cache\Step store(string|null $name = null)
 * @method static string getDefaultStore()
 *
 * @see \LaraGram\Cache\StepManager
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
        return 'step';
    }
}
