<?php

declare(strict_types=1);

namespace LaraGram\Filesystem;

use LaraGram\Filesystem\Exception\FilesystemException;
use RuntimeException;

class PathTraversalDetected extends RuntimeException implements FilesystemException
{
    private string $path;

    public function path(): string
    {
        return $this->path;
    }

    public static function forPath(string $path): PathTraversalDetected
    {
        $e = new PathTraversalDetected("Path traversal detected: {$path}");
        $e->path = $path;

        return $e;
    }
}
