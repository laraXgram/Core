<?php

namespace LaraGram\Support\Facades;

/**
 * @method static setRole(string $role, int|string $user_id, int|string|null $chat_id = null)
 * @method static addBotAdmin(int|string $user_id, int|string|null $chat_id = null)
 * @method static addBotOwner(int|string $user_id, int|string|null $chat_id = null)
 * @method static removeRole(int|string $user_id, int|string|null $chat_id = null)
 * @method static getRole(int|string $user_id, int|string|null $chat_id = null)
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 */
class Role extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'auth.role';
    }
}