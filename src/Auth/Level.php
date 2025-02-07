<?php

namespace LaraGram\Auth;

use App\Models\Admin;
use App\Models\User;
use LaraGram\Support\Traits\Macroable;

class Level
{
    use Macroable;

    /**
     * set level
     * @param string|int $level <p>
     * level name/number
     * </p>
     * @param int|string $user_id <p>
     * UserId. if null $userId = Message sender
     * </p>
     * @param int|string|null $chat_id <p>
     * ChatId. if null $chat_id = Current Chat
     * </p>
     * @return bool true if role removed, false
     * otherwise.
     */
    public function setLevel(string|int $level, int|string $user_id, int|string|null $chat_id = null): bool
    {
        $chat_id ??= chat()->id;

        $user = User::where('user_id', $user_id)->where('chat_id', $chat_id)->first();

        if (!is_null($user)){
            if (!isset($user->admin->level)){
                Admin::create([
                    'user_id' => $user->id,
                    'level' => $level
                ]);
                return true;
            }else{
                Admin::where('user_id', $user->id)->update([
                    'level' => $level
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
                'level' => $level
            ]);
            return true;
        }
    }

    /**
     * remove level
     * @param int|string $user_id <p>
     * UserId. if null $userId = Message sender
     * </p>
     * @param int|string|null $chat_id <p>
     * ChatId. if null $chat_id = Current Chat
     * </p>
     * @return bool true if role removed, false
     * otherwise.
     */
    public function removeLevel(int|string $user_id, int|string|null $chat_id = null): bool
    {
        $chat_id ??= chat()->id;

        $user = User::where('user_id', $user_id)->where('chat_id', $chat_id)->first();
        if (isset($user->admin->level)){
            Admin::where('user_id', $user->id)->delete();
            return true;
        }
        return false;
    }

    /**
     * get users level
     * @param int|string|null $user_id <p>
     * UserId. if null $userId = Message sender
     * </p>
     * @param int|string|null $chat_id <p>
     * ChatId. if null $chat_id = Current Chat
     * </p>
     * @return string|int|null users level
     * otherwise.
     */
    public function userLevel(int|string|null $user_id = null, int|string|null $chat_id = null): string|int|null
    {
        $chat_id ??= chat()->id;
        $user_id ??= user()->id;

        return User::where('user_id', $user_id)->where('chat_id', $chat_id)->first()->admin->level;
    }
}