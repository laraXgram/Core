<?php

namespace LaraGram\Conversation\Events;

use LaraGram\Conversation\Question;

class AnswerInvalid
{
    /**
     * Create a new event instance.
     *
     * @param  string  $name
     * @param  \LaraGram\Conversation\Question  $question
     * @param  array  $errors
     * @param  int  $attempt
     * @return void
     */
    public function __construct(
        public string $name,
        public Question $question,
        public array $errors,
        public int $attempt,
    ) {
    }
}
