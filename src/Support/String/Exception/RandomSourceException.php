<?php

namespace LaraGram\Support\String\Exception;

use RuntimeException as PhpRuntimeException;

/**
 * Thrown to indicate that the source of random data encountered an error
 *
 * This exception is used mostly to indicate that random_bytes() or random_int() threw an exception. However, it may be
 * used for other sources of random data.
 */
class RandomSourceException extends PhpRuntimeException implements UuidExceptionInterface
{
}
