<?php declare(strict_types=1);


namespace LaraGram\Log\Logger;

class_alias(JsonSerializableDateTimeImmutable::class, 'LaraGram\Log\Logger\DateTimeImmutable');

// @phpstan-ignore-next-line
if (false) {
    /**
     * @deprecated Use \LaraGram\Log\Logger\JsonSerializableDateTimeImmutable instead.
     */
    class DateTimeImmutable extends JsonSerializableDateTimeImmutable
    {
    }
}
