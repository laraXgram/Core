<?php

namespace LaraGram\Support\String\Exception;

use RuntimeException as PhpRuntimeException;

/**
 * Thrown to indicate that the source of time encountered an error
 */
class TimeSourceException extends PhpRuntimeException implements UuidExceptionInterface
{
}
