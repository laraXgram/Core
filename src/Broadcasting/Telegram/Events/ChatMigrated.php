<?php

namespace LaraGram\Broadcasting\Telegram\Events;

class ChatMigrated
{
    /**
     * Create a new event instance.
     *
     * A group was upgraded to a supergroup and received a new identifier.
     *
     * @param  string  $bot  The bot connection.
     * @param  int|string  $from  The old chat identifier.
     * @param  int|string  $to  The new chat identifier.
     */
    public function __construct(
        public string $bot,
        public int|string $from,
        public int|string $to,
    ) {
        //
    }
}
