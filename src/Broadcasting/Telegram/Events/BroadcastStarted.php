<?php

namespace LaraGram\Broadcasting\Telegram\Events;

class BroadcastStarted
{
    /**
     * Create a new event instance.
     *
     * @param  string  $id  The broadcast identifier.
     * @param  string  $bot  The bot connection sending the broadcast.
     * @param  string  $method  The Bot API method delivered to every recipient.
     * @param  array<int, string>  $audiences  The audiences and chats targeted.
     */
    public function __construct(
        public string $id,
        public string $bot,
        public string $method,
        public array $audiences,
    ) {
        //
    }
}
