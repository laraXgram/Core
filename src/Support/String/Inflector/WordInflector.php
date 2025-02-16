<?php

declare(strict_types=1);

namespace LaraGram\Support\String\Inflector;

interface WordInflector
{
    public function inflect(string $word): string;
}
