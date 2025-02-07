<?php

namespace LaraGram\Console\Completion;

class Suggestion implements \Stringable
{
    public function __construct(
        private readonly string $value,
        private readonly string $description = '',
    ) {
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function __toString(): string
    {
        return $this->getValue();
    }
}
