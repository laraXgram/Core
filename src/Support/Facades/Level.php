<?php

namespace LaraGram\Support\Facades;

/**
 * @method static userLevel(int|string|null $user_id = null, int|string|null $chat_id = null)
 * @method static setLevel(string|int $level, int|string $user_id, int|string|null $chat_id = null)
 * @method static removeLevel(int|string $user_id, int|string|null $chat_id = null)
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 */
class Level extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'auth.level';
    }
}