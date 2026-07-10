<?php

declare(strict_types=1);

namespace LaraGram\Filesystem\Connections\Ftp;

use RuntimeException;

final class UnableToEnableUtf8Mode extends RuntimeException implements FtpConnectionException
{
}
