<?php

declare(strict_types=1);

namespace LaraGram\Http\Client\Core\Exceptions;

use InvalidArgumentException;

/**
 * Exceptions thrown if a URI cannot be parsed because it's malformed.
 */
class MalformedUriException extends InvalidArgumentException
{
}
