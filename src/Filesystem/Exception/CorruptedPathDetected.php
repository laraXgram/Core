<?php

namespace LaraGram\Filesystem\Exception;

use RuntimeException;

final class CorruptedPathDetected extends RuntimeException implements FilesystemException
{
    public static function forPath(string $path): CorruptedPathDetected
    {
        return new CorruptedPathDetected("Corrupted path detected: " . $path);
    }
}
