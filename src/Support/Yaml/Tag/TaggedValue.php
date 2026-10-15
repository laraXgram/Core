<?php

namespace LaraGram\Support\Yaml\Tag;

final class TaggedValue
{
    public function __construct(
        private string $tag,
        private mixed $value,
    ) {
    }

    public function getTag(): string
    {
        return $this->tag;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }
}
