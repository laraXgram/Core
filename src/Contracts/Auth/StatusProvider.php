<?php

namespace LaraGram\Contracts\Auth;

interface StatusProvider
{
    /**
     * Retrieve the chat-member status of a user within a chat.
     *
     * @param  int|string  $userId
     * @param  int|string  $chatId
     * @return string|null
     */
    public function get($userId, $chatId);

    /**
     * Store (or update) the chat-member status of a user within a chat.
     *
     * @param  int|string  $userId
     * @param  int|string  $chatId
     * @param  array  $attributes
     * @return void
     */
    public function put($userId, $chatId, array $attributes);
}
