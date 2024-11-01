<?php

namespace LaraGram\Auth;

use App\Models\Admin;
use App\Models\User;
use LaraGram\Support\Traits\Macroable;

class Role
{
    use Macroable;

    public function setRole(string $role, int|string $user_id, int|string|null $chat_id = null): bool
    {
        $chat_id ??= chat()->id;

        $user = User::where('user_id', $user_id)->where('chat_id', $chat_id)->first();

        if (!is_null($user)){
            if (!isset($user->admin->role)){
                Admin::create([
                    'user_id' => $user->id,
                    'role' => $role
                ]);
                return true;
            }else{
                Admin::where('user_id', $user->id)->update([
                    'role' => $role
                ]);
                return true;
            }
        }else{
            $user = User::create([
                'first_name' => user()->first_name,
                'last_name'  => user()->last_name ?? null,
                'user_id'    => $user_id,
                'chat_id'    => $chat_id
            ]);

            Admin::create([
                'user_id' => $user->id,
                'role' => $role
            ]);
            return true;
        }
    }

    /**
     * add new bot admin
     * @param int|string $user_id <p>
     * UserId. if null $userId = Message sender
     * </p>
     * @param int|string|null $chat_id <p>
     * ChatId. if null $chat_id = Current Chat
     * </p>
     * @return bool true if admin set, false
     * otherwise.
     */
    public function addBotAdmin(int|string $user_id, int|string|null $chat_id = null): bool
    {
        return self::setRole('administrator', $user_id, $chat_id);
    }

    /**
     * add new bot owner
     * @param int|string $user_id <p>
     * UserId. if null $userId = Message sender
     * </p>
     * @param int|string|null $chat_id <p>
     * ChatId. if null $chat_id = Current Chat
     * </p>
     * @return bool true if owner set, false
     * otherwise.
     */
    public function addBotOwner(int|string $user_id, int|string|null $chat_id = null): bool
    {
        return self::setRole('owner', $user_id, $chat_id);
    }

    /**
     * remove member role
     * @param int|string $user_id <p>
     * UserId. if null $userId = Message sender
     * </p>
     * @param int|string|null $chat_id <p>
     * ChatId. if null $chat_id = Current Chat
     * </p>
     * @return bool true if role removed, false
     * otherwise.
     */
    public function removeRole(int|string $user_id, int|string|null $chat_id = null): bool
    {
        $chat_id ??= chat()->id;

        $user = User::where('user_id', $user_id)->where('chat_id', $chat_id)->first();
        if (isset($user->admin->role)){
            Admin::where('user_id', $user->id)->delete();
            return true;
        }
        return false;
    }

    /**
     * Get member role
     * @param int|string $user_id <p>
     * UserId. if null $userId = Message sender
     * </p>
     * @param int|string|null $chat_id <p>
     * ChatId. if null $chat_id = Current Chat
     * </p>
     * @return string|null true if role removed, false
     * otherwise.
     */
    public function getRole(int|string $user_id, int|string|null $chat_id = null): string|null
    {
        $chat_id ??= chat()->id;

        $user = User::where('user_id', $user_id)->where('chat_id', $chat_id)->first();
        if (isset($user->admin->role)){
            return $user->admin->role;
        }
        return null;
    }
}