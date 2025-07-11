<?php

namespace LaraGram\Listening\Expression;

class Token
{
    public const EOF_TYPE = 'end of expression';
    public const NAME_TYPE = 'name';
    public const NUMBER_TYPE = 'number';
    public const STRING_TYPE = 'string';
    public const OPERATOR_TYPE = 'operator';
    public const PUNCTUATION_TYPE = 'punctuation';

    /**
     * @param self::*_TYPE $type
     * @param int|null     $cursor The cursor position in the source
     */
    public function __construct(
        public string $type,
        public string|int|float|null $value,
        public ?int $cursor,
    ) {
    }

    /**
     * Returns a string representation of the token.
     */
    public function __toString(): string
    {
        return \sprintf('%3d %-11s %s', $this->cursor, strtoupper($this->type), $this->value);
    }

    /**
     * Tests the current token for a type and/or a value.
     */
    public function test(string $type, ?string $value = null): bool
    {
        return $this->type === $type && (null === $value || $this->value == $value);
    }
}
