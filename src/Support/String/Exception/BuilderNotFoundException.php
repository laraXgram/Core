<?php

namespace LaraGram\Support\String\Exception;

use RuntimeException as PhpRuntimeException;

/**
 * Thrown to indicate that no suitable builder could be found
 */
class BuilderNotFoundException extends PhpRuntimeException implements UuidExceptionInterface
{
}
