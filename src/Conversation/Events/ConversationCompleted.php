<?php

namespace LaraGram\Conversation\Events;

use LaraGram\Conversation\AnswersBag;

class ConversationCompleted
{
    /**
     * Create a new event instance.
     *
     * @param  string  $name
     * @param  \LaraGram\Conversation\AnswersBag  $answers
     * @return void
     */
    public function __construct(
        public string $name,
        public AnswersBag $answers,
    ) {
    }
}
