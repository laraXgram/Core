<?php

namespace LaraGram\Conversation\Events;

use LaraGram\Conversation\Question;

class QuestionSkipped
{
    /**
     * Create a new event instance.
     *
     * @param  string  $name
     * @param  \LaraGram\Conversation\Question  $question
     * @return void
     */
    public function __construct(
        public string $name,
        public Question $question,
    ) {
    }
}
