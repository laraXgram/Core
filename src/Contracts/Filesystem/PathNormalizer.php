<?php

declare(strict_types=1);

namespace LaraGram\Contracts\Filesystem;

interface PathNormalizer
{
    public function normalizePath(string $path): string;
}
