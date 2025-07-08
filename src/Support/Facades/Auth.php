<?php

namespace LaraGram\Support\Facades;

/**
 * @method static getStatus(int|string|null $user_id = null, int|string|null $chat_id = null)
 * @method static isChatAdmin(int|string|null $user_id = null, int|string|null $chat_id = null)
 * @method static isChatCreator(int|string|null $user_id = null, int|string|null $chat_id = null)
 * @method static isChatMember(int|string|null $user_id = null, int|string|null $chat_id = null)
 * @method static isKicked(int|string|null $user_id = null, int|string|null $chat_id = null)
 * @method static isRestricted(int|string|null $user_id = null, int|string|null $chat_id = null)
 * @method static isLeft(int|string|null $user_id = null, int|string|null $chat_id = null)
 * @method static isBotAdmin(int|string|null $user_id = null, int|string|null $chat_id = null)
 * @method static isBotOwner(int|string|null $user_id = null, int|string|null $chat_id = null)
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 */
class Auth extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'auth';
    }
}