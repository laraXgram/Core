<?php

namespace LaraGram\Template\Rich\Html;

/**
 * A single piece of parsed rich markup: a run of text, or a tag.
 */
final class Token
{
    public const TEXT = 'text';
    public const OPEN = 'open';
    public const CLOSE = 'close';

    /**
     * @param  string  $kind  One of the TEXT, OPEN or CLOSE constants.
     * @param  string  $name  Tag name for OPEN and CLOSE tokens, empty for TEXT.
     * @param  array<string, string|true>  $attributes  Attribute values, or true when valueless.
     * @param  string  $text  Decoded text content for TEXT tokens.
     * @param  bool  $selfClosing  Whether an OPEN token was written as <tag/>.
     * @param  int  $offset  Byte offset in the source markup, for error messages.
     * @param  bool  $preserve  Whether a TEXT token's whitespace is significant.
     */
    public function __construct(
        public string $kind,
        public string $name = '',
        public array $attributes = [],
        public string $text = '',
        public bool $selfClosing = false,
        public int $offset = 0,
        public bool $preserve = false,
    ) {
    }

    /**
     * Determine whether this token is a run of text.
     *
     * @return bool
     */
    public function isText(): bool
    {
        return $this->kind === self::TEXT;
    }

    /**
     * Determine whether this token opens a tag.
     *
     * @return bool
     */
    public function isOpen(): bool
    {
        return $this->kind === self::OPEN;
    }

    /**
     * Determine whether this token closes a tag.
     *
     * @return bool
     */
    public function isClose(): bool
    {
        return $this->kind === self::CLOSE;
    }

    /**
     * Get an attribute value, or null when it is absent or valueless.
     *
     * @param  string  $name
     * @return string|null
     */
    public function attribute(string $name): ?string
    {
        $value = $this->attributes[$name] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * Determine whether an attribute is present, with or without a value.
     *
     * @param  string  $name
     * @return bool
     */
    public function hasAttribute(string $name): bool
    {
        return array_key_exists($name, $this->attributes);
    }
}
