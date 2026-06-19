<?php

namespace LaraGram\Conversation\Events;

use LaraGram\Conversation\Question;

class AnswerReceived
{
    /**
     * Create a new event instance.
     *
     * @param  string  $name
     * @param  \LaraGram\Conversation\Question  $question
     * @param  mixed  $answer
     * @return void
     */
    public function __construct(
        public string $name,
        public Question $question,
        public mixed $answer,
    ) {
    }
}
