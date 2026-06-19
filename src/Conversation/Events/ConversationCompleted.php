<?php

namespace LaraGram\Conversation\Events;

class ConversationCompleted
{
    /**
     * Create a new event instance.
     *
     * @param  string  $name
     * @param  array<string, mixed>  $answers
     * @return void
     */
    public function __construct(
        public string $name,
        public array $answers,
    ) {
    }
}
