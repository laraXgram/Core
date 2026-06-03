<?php

declare(strict_types=1);

namespace LaraGram\Filesystem\Exception;

use LaraGram\Contracts\Filesystem\FilesystemException;
use RuntimeException;
use Throwable;

final class UnableToProvideChecksum extends RuntimeException implements FilesystemException
{
    public function __construct(string $reason, string $path, ?Throwable $previous = null)
    {
        parent::__construct("Unable to get checksum for $path: $reason", 0, $previous);
    }
}
