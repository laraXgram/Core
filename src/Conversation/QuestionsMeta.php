<?php

namespace LaraGram\Conversation;

class QuestionsMeta
{
    public function __construct(private int &$current, private array &$questions) { }
    public function name(string $name): static
    {
        $this->questions[$this->current]['name'] = $name;

        return $this;
    }

    public function type(string|array $type): static
    {
        $this->questions[$this->current]['type'] = $type;

        return $this;
    }

    public function media(string $type, string $file_id): static
    {
        $this->questions[$this->current]['media'] = [$type, $file_id];

        return $this;
    }

    public function keyboard(string $keyboard): static
    {
        $this->questions[$this->current]['keyboard'] = $keyboard;

        return $this;
    }

    public function skipCommand(string $command): static
    {
        $this->questions[$this->current]['skip_command'] = $command;

        return $this;
    }

    public function getQuestions(): array
    {
        return $this->questions;
    }
}