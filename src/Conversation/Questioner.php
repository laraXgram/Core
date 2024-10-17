<?php

namespace LaraGram\Conversation;

class Questioner
{
    private int $current = -1;
    private array $questions = [];
    private QuestionsMeta $questionsMeta;

    public function __construct() {
        $this->questionsMeta = new QuestionsMeta($this->current, $this->questions);
    }

    public function ask(string $question): QuestionsMeta
    {
        $this->current++;

        $this->questions[$this->current]['question'] = $question;

        return $this->questionsMeta;
    }

    public function getQuestions(): array
    {
        return $this->questions;
    }
}