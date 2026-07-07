<?php

namespace LaraGram\Support\Facades;

/**
 * @method static void start(string $name, array $parameters = [])
 * @method static void create(\Closure $callback)
 * @method static bool handle(\LaraGram\Request\Request $request)
 * @method static bool active()
 * @method static array|null state()
 * @method static array answers()
 * @method static void cancel(string $reason = 'manual')
 *
 * @see \LaraGram\Conversation\ConversationManager
 */
class Conversation extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'conversation';
    }
}
