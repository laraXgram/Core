<?php

namespace LaraGram\Conversation\Events;

use LaraGram\Conversation\Conversation;

class ConversationStarted
{
    /**
     * Create a new event instance.
     *
     * @param  string  $name
     * @param  \LaraGram\Conversation\Conversation  $conversation
     * @return void
     */
    public function __construct(
        public string $name,
        public Conversation $conversation,
    ) {
    }
}
