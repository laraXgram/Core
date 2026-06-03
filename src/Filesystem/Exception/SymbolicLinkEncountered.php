<?php

declare(strict_types=1);

namespace LaraGram\Filesystem\Exception;

use LaraGram\Contracts\Filesystem\FilesystemException;
use RuntimeException;

final class SymbolicLinkEncountered extends RuntimeException implements FilesystemException
{
    private string $location;

    public function location(): string
    {
        return $this->location;
    }

    public static function atLocation(string $pathName): SymbolicLinkEncountered
    {
        $e = new static("Unsupported symbolic link encountered at location $pathName");
        $e->location = $pathName;

        return $e;
    }
}
