<?php

namespace LaraGram\Conversation;

/**
 * Collects the questions that make up a conversation.
 */
class Questioner
{
    /**
     * The declared questions.
     *
     * @var array<int, \LaraGram\Conversation\Question>
     */
    protected $questions = [];

    /**
     * Declare a new question.
     *
     * @param  string  $prompt
     * @return \LaraGram\Conversation\Question
     */
    public function ask(string $prompt): Question
    {
        return $this->questions[] = new Question($prompt);
    }

    /**
     * Get all declared questions.
     *
     * @return array<int, \LaraGram\Conversation\Question>
     */
    public function all(): array
    {
        return $this->questions;
    }

    /**
     * Get the question at the given index.
     *
     * @param  int  $index
     * @return \LaraGram\Conversation\Question|null
     */
    public function get(int $index): ?Question
    {
        return $this->questions[$index] ?? null;
    }

    /**
     * Get the number of declared questions.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->questions);
    }

    /**
     * Determine if no questions have been declared.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->questions === [];
    }
}
