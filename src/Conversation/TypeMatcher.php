<?php

namespace LaraGram\Conversation;

/**
 * Matches and extracts answer values from an incoming update by content type.
 */
class TypeMatcher
{
    /**
     * Determine whether the incoming update satisfies the given type and
     * return the extracted answer value.
     *
     * @param  string  $type
     * @return array{0: bool, 1: mixed}  [matched, value]
     */
    public static function extract(string $type): array
    {
        $type = strtolower(trim($type));

        // Convenience aliases mapping to real Telegram message field names.
        $type = self::ALIASES[$type] ?? $type;

        return match ($type) {
            'any'  => self::extractAny(),
            'text' => self::extractText(),
            'keyboard', 'callback', 'callback_query', 'callback_data'
                   => self::extractCallback(),
            default => self::extractField($type),
        };
    }

    /**
     * Convenience type aliases mapped to real Telegram message field names.
     *
     * @var array<string, string>
     */
    protected const ALIASES = [
        'gif'           => 'animation',
        'image'         => 'photo',
        'img'           => 'photo',
        'photos'        => 'photo',
        'file'          => 'document',
        'doc'           => 'document',
        'videonote'     => 'video_note',
        'video_message' => 'video_note',
        'voice_message' => 'voice',
        'gps'           => 'location',
        'place'         => 'venue',
    ];

    /**
     * Extract any meaningful value from the update.
     *
     * @return array{0: bool, 1: mixed}
     */
    protected static function extractAny(): array
    {
        if (($text = text()) !== null && $text !== '') {
            return [true, $text];
        }

        if (($callback = callback_query()) !== null) {
            return [true, $callback->data ?? null];
        }

        $message = message();

        return [$message !== null, $message];
    }

    /**
     * Extract a non-empty text answer.
     *
     * @return array{0: bool, 1: mixed}
     */
    protected static function extractText(): array
    {
        $text = text();

        return [$text !== null && $text !== '', $text];
    }

    /**
     * Extract a callback query answer.
     *
     * @return array{0: bool, 1: mixed}
     */
    protected static function extractCallback(): array
    {
        $callback = callback_query();

        return [$callback !== null, $callback->data ?? null];
    }

    /**
     * Extract a named field from the message (contact, photo, location, ...).
     *
     * @param  string  $field
     * @return array{0: bool, 1: mixed}
     */
    protected static function extractField(string $field): array
    {
        $message = message();

        if ($message === null || ! isset($message->{$field}) || $message->{$field} === null) {
            return [false, null];
        }

        return [true, $message->{$field}];
    }
}
