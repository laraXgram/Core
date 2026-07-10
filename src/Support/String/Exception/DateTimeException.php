<?php

namespace LaraGram\Support\String\Exception;

use RuntimeException as PhpRuntimeException;

/**
 * Thrown to indicate that the PHP DateTime extension encountered an exception/error
 */
class DateTimeException extends PhpRuntimeException implements UuidExceptionInterface
{
}
