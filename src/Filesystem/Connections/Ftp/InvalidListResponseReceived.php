<?php

declare(strict_types=1);

namespace LaraGram\Filesystem\Connections\Ftp;

use RuntimeException;

class InvalidListResponseReceived extends RuntimeException implements FilesystemException
{
}
