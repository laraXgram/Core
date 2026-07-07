<?php

namespace LaraGram\Conversation\Events;

use LaraGram\Conversation\Question;

class BackRequested
{
    /**
     * Create a new event instance.
     *
     * @param  string  $name
     * @param  \LaraGram\Conversation\Question  $question  The previous question being re-asked.
     * @return void
     */
    public function __construct(
        public string $name,
        public Question $question,
    ) {
    }
}
