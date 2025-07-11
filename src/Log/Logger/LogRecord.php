<?php declare(strict_types=1);


namespace LaraGram\Log\Logger;

use ArrayAccess;

class LogRecord implements ArrayAccess
{
    private const MODIFIABLE_FIELDS = [
        'extra' => true,
        'formatted' => true,
    ];

    public function __construct(
        public readonly \DateTimeImmutable $datetime,
        public readonly string $channel,
        public readonly Level $level,
        public readonly string $message,
        /** @var array<mixed> */
        public readonly array $context = [],
        /** @var array<mixed> */
        public array $extra = [],
        public mixed $formatted = null,
    ) {
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === 'extra') {
            if (!\is_array($value)) {
                throw new \InvalidArgumentException('extra must be an array');
            }

            $this->extra = $value;

            return;
        }

        if ($offset === 'formatted') {
            $this->formatted = $value;

            return;
        }

        throw new \LogicException('Unsupported operation: setting '.$offset);
    }

    public function offsetExists(mixed $offset): bool
    {
        if ($offset === 'level_name') {
            return true;
        }

        return isset($this->{$offset});
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \LogicException('Unsupported operation');
    }

    public function &offsetGet(mixed $offset): mixed
    {
        // handle special cases for the level enum
        if ($offset === 'level_name') {
            // avoid returning readonly props by ref as this is illegal
            $copy = $this->level->getName();

            return $copy;
        }
        if ($offset === 'level') {
            // avoid returning readonly props by ref as this is illegal
            $copy = $this->level->value;

            return $copy;
        }

        if (isset(self::MODIFIABLE_FIELDS[$offset])) {
            return $this->{$offset};
        }

        // avoid returning readonly props by ref as this is illegal
        $copy = $this->{$offset};

        return $copy;
    }

    /**
     * @phpstan-return array{message: string, context: mixed[], level: value-of<Level::VALUES>, level_name: value-of<Level::NAMES>, channel: string, datetime: \DateTimeImmutable, extra: mixed[]}
     */
    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'context' => $this->context,
            'level' => $this->level->value,
            'level_name' => $this->level->getName(),
            'channel' => $this->channel,
            'datetime' => $this->datetime,
            'extra' => $this->extra,
        ];
    }

    public function with(mixed ...$args): self
    {
        foreach (['message', 'context', 'level', 'channel', 'datetime', 'extra'] as $prop) {
            $args[$prop] ??= $this->{$prop};
        }

        return new self(...$args);
    }
}
