<?php

declare(strict_types=1);

namespace LaraGram\Http\Client\Promises;

/**
 * Exceptions that is set as the reason for a promise that has been cancelled.
 */
class CancellationException extends RejectionException
{
}
