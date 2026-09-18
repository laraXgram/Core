<?php

namespace LaraGram\Conversation;

use Closure;

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
     * The prompt may be a closure receiving the answers given so far, or be
     * left out entirely when the question renders itself with a template.
     *
     * @param  string|\Closure|null  $prompt
     * @return \LaraGram\Conversation\Question
     */
    public function ask(string|Closure|null $prompt = null): Question
    {
        return $this->questions[] = new Question($prompt);
    }

    /**
     * Declare a question that offers a fixed set of options.
     *
     * @param  string|\Closure|null  $prompt
     * @param  array<int|string, string>|\Closure  $options
     * @return \LaraGram\Conversation\Question
     */
    public function choose(string|Closure|null $prompt, array|Closure $options): Question
    {
        return $this->ask($prompt)->choices($options);
    }

    /**
     * Declare a yes or no question, answered with a boolean.
     *
     * @param  string|\Closure|null  $prompt
     * @param  string  $yes
     * @param  string  $no
     * @return \LaraGram\Conversation\Question
     */
    public function confirm(string|Closure|null $prompt, string $yes = 'Yes', string $no = 'No'): Question
    {
        return $this->ask($prompt)->confirm($yes, $no);
    }

    /**
     * Declare a question whose prompt is rendered by a template.
     *
     * @param  string  $template
     * @param  array<string, mixed>  $data
     * @return \LaraGram\Conversation\Question
     */
    public function template(string $template, array $data = []): Question
    {
        return $this->ask()->template($template, $data);
    }

    /**
     * Get the index of the question with the given name.
     *
     * @param  string  $name
     * @return int|null
     */
    public function indexOf(string $name): ?int
    {
        foreach ($this->questions as $index => $question) {
            if (QuestionAccessor::compile($question)->name === $name) {
                return $index;
            }
        }

        return null;
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
