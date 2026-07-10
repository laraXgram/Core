<?php

namespace LaraGram\Support\String\Exception;

use RuntimeException as PhpRuntimeException;

/**
 * Thrown to indicate a builder is unable to build a UUID
 */
class UnableToBuildUuidException extends PhpRuntimeException implements UuidExceptionInterface
{
}
