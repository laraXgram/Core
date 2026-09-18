<?php

namespace LaraGram\Broadcasting\Telegram\Events;

class DeliveryFailed
{
    /**
     * Create a new event instance.
     *
     * @param  string  $id  The broadcast identifier.
     * @param  string  $bot  The bot connection.
     * @param  int|string  $chatId  The recipient.
     * @param  string  $method  The Bot API method.
     * @param  int  $errorCode  The Bot API error code (0 for an exception).
     * @param  string  $description  The error description.
     */
    public function __construct(
        public string $id,
        public string $bot,
        public int|string $chatId,
        public string $method,
        public int $errorCode,
        public string $description,
    ) {
        //
    }
}
