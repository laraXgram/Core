<?php

namespace LaraGram\Console\Prompts\Support;

final class Result
{
    public function __construct(public readonly mixed $value)
    {
        //
    }

    public static function from(mixed $value): self
    {
        return new self($value);
    }
}
