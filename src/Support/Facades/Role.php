<?php

namespace LaraGram\Support\Facades;

/**
 * @method static setRole(string $role, int|string $user_id, int|string|null $chat_id = null)
 * @method static addBotAdmin(int|string $user_id, int|string|null $chat_id = null)
 * @method static addBotOwner(int|string $user_id, int|string|null $chat_id = null)
 * @method static removeRole(int|string $user_id, int|string|null $chat_id = null)
 * @method static getRole(int|string $user_id, int|string|null $chat_id = null)
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