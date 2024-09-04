<?php

namespace LaraGram\Auth;

use App\Models\User;
use LaraGram\Support\Trait\Macroable;

class Auth
{
    use Macroable;

    private static mixed $status = null;

    /**
     * Check user statis
     * @param int|string|null $user_id <p>
     * UserId. if null $userId = Message sender
     * </p>
     * @param int|string|null $chat_id
     * ChatId. if null $chat_id = Current Chat
     * </p>
     * @return string|null user status
     */
    public function getStatus(int|string|null $user_id = null, int|string|null $chat_id = null)
    {
        $chat_id ??= chat()->id;
        $user_id ??= user()->id;

        if (static::$status == null) {
            static::$status = app('request')->getChatMember($chat_id, $user_id)['result']['status'];
        }

        return static::$status;
    }

    /**
     * Check is chat admin or not
     * @param int|string|null $user_id <p>
     * UserId. if null $userId = Message sender
     * </p>
     * @param int|string|null $chat_id <p>
     * ChatId. if null $chat_id = Current Chat
     * </p>
     * @return bool true if is admin, false
     * otherwise.
     */
    public function isChatAdmin(int|string|null $user_id = null, int|string|null $chat_id = null): bool
    {
        if ($this->getStatus($user_id, $chat_id) === 'administrator') {
            return true;
        }
        return false;
    }

    /**
     * Check is chat creator or not
     * @param int|string|null $user_id <p>
     * UserId. if null $userId = Message sender
     * </p>
     * @param int|string|null $chat_id <p>
     * ChatId. if null $chat_id = Current Chat
     * </p>
     * @return bool true if is creator, false
     * otherwise.
     */
    public function isChatCreator(int|string|null $user_id = null, int|string|null $chat_id = null): bool
    {
        if ($this->getStatus($user_id, $chat_id) === 'creator') {
            return true;
        }
        return false;
    }

    /**
     * Check is chat member or not
     * @param int|string|null $user_id <p>
     * UserId. if null $userId = Message sender
     * </p>
     * @param int|string|null $chat_id <p>
     * ChatId. if null $chat_id = Current Chat
     * </p>
     * @return bool true if is member, false
     * otherwise.
     */
    public function isChatMember(int|string|null $user_id = null, int|string|null $chat_id = null): bool
    {
        if ($this->getStatus($user_id, $chat_id) === 'member') {
            return true;
        }
        return false;
    }

    /**
     * Check is kicked member or not
     * @param int|string|null $user_id <p>
     * UserId. if null $userId = Message sender
     * </p>
     * @param int|string|null $chat_id <p>
     * ChatId. if null $chat_id = Current Chat
     * </p>
     * @return bool true if is member, false
     * otherwise.
     */
    public function isKicked(int|string|null $user_id = null, int|string|null $chat_id = null): bool
    {
        if ($this->getStatus($user_id, $chat_id) === 'kicked') {
            return true;
        }
        return false;
    }

    /**
     * Check is restricted member or not
     * @param int|string|null $user_id <p>
     * UserId. if null $userId = Message sender
     * </p>
     * @param int|string|null $chat_id <p>
     * ChatId. if null $chat_id = Current Chat
     * </p>
     * @return bool true if is member, false
     * otherwise.
     */
    public function isRestricted(int|string|null $user_id = null, int|string|null $chat_id = null): bool
    {
        if ($this->getStatus($user_id, $chat_id) === 'restricted') {
            return true;
        }
        return false;
    }

    /**
     * Check is left member or not
     * @param int|string|null $user_id <p>
     * UserId. if null $userId = Message sender
     * </p>
     * @param int|string|null $chat_id <p>
     * ChatId. if null $chat_id = Current Chat
     * </p>
     * @return bool true if is member, false
     * otherwise.
     */
    public function isLeft(int|string|null $user_id = null, int|string|null $chat_id = null): bool
    {
        if ($this->getStatus($user_id, $chat_id) === 'left') {
            return true;
        }
        return false;
    }

    /**
     * Check is bot admin or not
     * @param int|string|null $user_id <p>
     * UserId. if null $userId = Message sender
     * </p>
     * @param int|string|null $chat_id <p>
     * ChatId. if null $chat_id = Current Chat
     * </p>
     * @return bool true if is admin, false
     * otherwise.
     */
    public function isBotAdmin(int|string|null $user_id = null, int|string|null $chat_id = null): bool
    {
        $chat_id ??= chat()->id;
        $user_id ??= user()->id;

        $role = User::where('user_id', $user_id)->where('chat_id', $chat_id)->first()->admin->role;
        if ($role === 'administrator') {
            return true;
        }
        return false;
    }

    /**
     * Check is bot owner or not
     * @param int|string|null $user_id <p>
     * UserId. if null $userId = Message sender
     * </p>
     * @param int|string|null $chat_id <p>
     * ChatId. if null $chat_id = Current Chat
     * </p>
     * @return bool true if is owner, false
     * otherwise.
     */
    public function isBotOwner(int|string|null $user_id = null, int|string|null $chat_id = null): bool
    {
        $chat_id ??= chat()->id;
        $user_id ??= user()->id;

        $role = User::where('user_id', $user_id)->where('chat_id', $chat_id)->first()->admin->role;
        if ($role === 'owner') {
            return true;
        }
        return false;
    }
}