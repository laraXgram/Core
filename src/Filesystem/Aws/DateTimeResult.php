<?php

namespace LaraGram\Filesystem\Aws;

use DateTimeImmutable;
use DateTimeZone;
use JsonSerializable;
use Stringable;

class DateTimeResult extends DateTimeImmutable implements JsonSerializable, Stringable
{
    /**
     * Create a date from an AWS timestamp: ISO 8601, RFC 7231 or epoch seconds.
     *
     * @param  string|int|float  $value
     * @return static
     */
    public static function fromTimestamp(string|int|float $value): static
    {
        if (is_numeric($value)) {
            return new static('@'.(int) $value);
        }

        return (new static($value))->setTimezone(new DateTimeZone('UTC'));
    }

    /**
     * Get the date as an ISO 8601 string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->format('Y-m-d\TH:i:s.v\Z');
    }

    /**
     * Get the date for JSON serialization.
     *
     * @return string
     */
    public function jsonSerialize(): string
    {
        return (string) $this;
    }
}
