<?php

declare(strict_types=1);

namespace LaraGram\Support\String\Inflector;

class NoopWordInflector implements WordInflector
{
    public function inflect(string $word): string
    {
        return $word;
    }
}
