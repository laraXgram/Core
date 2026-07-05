<?php

namespace LaraGram\Conversation\Events;

class ConversationCancelled
{
    /**
     * Create a new event instance.
     *
     * @param  string|null  $name
     * @param  string  $reason
     * @return void
     */
    public function __construct(
        public ?string $name,
        public string $reason,
    ) {
    }
}
