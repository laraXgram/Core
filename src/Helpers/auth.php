<?php

use LaraGram\Support\Facades\Auth;
use LaraGram\Support\Facades\Level;
use LaraGram\Support\Facades\Role;

if (!function_exists('get_status')) {
    function get_status(int|string|null $user_id = null, int|string|null $chat_id = null): string|null
    {
        return Auth::getStatus($user_id, $chat_id);
    }
}

if (!function_exists('is_chat_admin')) {
    function is_chat_admin(int|string|null $user_id = null, int|string|null $chat_id = null): bool
    {
        return Auth::isChatAdmin($user_id, $chat_id);
    }
}

if (!function_exists('is_chat_creator')) {
    function is_chat_creator(int|string|null $user_id = null, int|string|null $chat_id = null): bool
    {
        return Auth::isChatCreator($user_id, $chat_id);
    }
}

if (!function_exists('is_chat_member')) {
    function is_chat_member(int|string|null $user_id = null, int|string|null $chat_id = null): bool
    {
        return Auth::isChatMember($user_id, $chat_id);
    }
}

if (!function_exists('is_kicked')) {
    function is_kicked(int|string|null $user_id = null, int|string|null $chat_id = null): string|null
    {
        return Auth::isKicked($user_id, $chat_id);
    }
}

if (!function_exists('is_restricted')) {
    function is_restricted(int|string|null $user_id = null, int|string|null $chat_id = null): bool
    {
        return Auth::isRestricted($user_id, $chat_id);
    }
}

if (!function_exists('is_left')) {
    function is_left(int|string|null $user_id = null, int|string|null $chat_id = null): bool
    {
        return Auth::isLeft($user_id, $chat_id);
    }
}

if (!function_exists('is_bot_admin')) {
    function is_bot_admin(int|string|null $user_id = null, int|string|null $chat_id = null): bool
    {
        return Auth::isBotAdmin($user_id, $chat_id);
    }
}

if (!function_exists('is_bot_owner')) {
    function is_bot_owner(int|string|null $user_id = null, int|string|null $chat_id = null): bool
    {
        return Auth::isBotOwner($user_id, $chat_id);
    }
}

if (!function_exists('set_role')) {
    function set_role(string $role, int|string $user_id, int|string|null $chat_id = null): bool
    {
        return Role::setRole($role, $user_id, $chat_id);
    }
}

if (!function_exists('add_bot_admin')) {
    function add_bot_admin(int|string $user_id, int|string|null $chat_id = null): bool
    {
        return Role::addBotAdmin($user_id, $chat_id);
    }
}

if (!function_exists('add_bot_owner')) {
    function add_bot_owner(int|string $user_id, int|string|null $chat_id = null): bool
    {
        return Role::addBotOwner($user_id, $chat_id);
    }
}

if (!function_exists('remove_role')) {
    function remove_role(int|string $user_id, int|string|null $chat_id = null): bool
    {
        return Role::removeRole($user_id, $chat_id);
    }
}

if (!function_exists('set_level')) {
    function set_level(string|int $level, int|string $user_id, int|string|null $chat_id = null): bool
    {
        return Level::setLevel($level, $user_id, $chat_id);
    }
}

if (!function_exists('remove_level')) {
    function remove_level(int|string $user_id, int|string|null $chat_id = null): bool
    {
        return Level::removeLevel($user_id, $chat_id);
    }
}

if (!function_exists('user_level')) {
    function user_level(int|string $user_id, int|string|null $chat_id = null): bool
    {
        return Level::userLevel($user_id, $chat_id);
    }
}