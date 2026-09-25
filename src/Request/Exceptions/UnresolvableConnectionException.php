<?php

namespace LaraGram\Request\Exceptions;

use RuntimeException;

class UnresolvableConnectionException extends RuntimeException
{
    /**
     * Create a new exception for an update no configured bot connection could claim.
     *
     * @param  int|string|null  $updateId
     * @return static
     */
    public static function forUpdate(int|string|null $updateId): static
    {
        $update = $updateId === null ? 'the incoming update' : "update [{$updateId}]";

        return new static(
            "Unable to detect which bot connection {$update} belongs to. With [bot.default] set to 'auto', give each "
            ."connection a unique 'secret_token' (and re-run webhook:set) or a unique webhook 'url'."
        );
    }
}
