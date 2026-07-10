<?php

namespace LaraGram\Support\String\Exception;

/**
 * Thrown to indicate that the string received is not a valid UUID
 *
 * The InvalidArgumentException that this extends is the ramsey/uuid version of this exception. It exists in the same
 * namespace as this class.
 */
class InvalidUuidStringException extends InvalidArgumentException implements UuidExceptionInterface
{
}
