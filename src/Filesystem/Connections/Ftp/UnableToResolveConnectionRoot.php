<?php

declare(strict_types=1);

namespace LaraGram\Filesystem\Connections\Ftp;

use RuntimeException;
use Throwable;

final class UnableToResolveConnectionRoot extends RuntimeException implements FtpConnectionException
{
    private function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    public static function itDoesNotExist(string $root, string $reason = ''): UnableToResolveConnectionRoot
    {
        return new UnableToResolveConnectionRoot(
            'Unable to resolve connection root. It does not seem to exist: ' . $root . "\nreason: $reason"
        );
    }

    public static function couldNotGetCurrentDirectory(string $message = ''): UnableToResolveConnectionRoot
    {
        return new UnableToResolveConnectionRoot(
            'Unable to resolve connection root. Could not resolve the current directory. ' . $message
        );
    }
}
