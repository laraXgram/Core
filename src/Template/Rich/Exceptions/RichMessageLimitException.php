<?php

namespace LaraGram\Template\Rich\Exceptions;

class RichMessageLimitException extends RichMessageException
{
    /**
     * Create an exception for a rich message limit that has been exceeded.
     *
     * @param  string  $limit
     * @param  int|string  $actual
     * @param  int  $maximum
     * @return static
     */
    public static function exceeded(string $limit, int|string $actual, int $maximum): static
    {
        return new static(
            "Rich message {$limit} limit exceeded: {$actual} used, {$maximum} allowed."
        );
    }
}
