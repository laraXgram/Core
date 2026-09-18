<?php

namespace LaraGram\Broadcasting\Telegram\Events;

class ChatUnreachable
{
    /**
     * Create a new event instance.
     *
     * The chat blocked or removed the bot, was deleted, or no longer exists.
     * It has been marked unreachable in the chat repository.
     *
     * @param  string  $id  The broadcast identifier.
     * @param  string  $bot  The bot connection.
     * @param  int|string  $chatId  The unreachable chat.
     * @param  int  $errorCode  The Bot API error code.
     * @param  string  $description  The Bot API error description.
     */
    public function __construct(
        public string $id,
        public string $bot,
        public int|string $chatId,
        public int $errorCode,
        public string $description,
    ) {
        //
    }
}
