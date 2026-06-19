<?php

declare(strict_types=1);

namespace LaraGram\Filesystem\Exception;

use InvalidArgumentException as BaseInvalidArgumentException;

class InvalidStreamProvided extends BaseInvalidArgumentException implements FilesystemException
{
}
