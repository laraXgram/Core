<?php

namespace LaraGram\Support;

use InvalidArgumentException;
use LaraGram\Support\String\Uid\Ulid;

class BinaryCodec
{
    /** @var array<string, array{encode: callable(Ulid|string|null): ?string, decode: callable(?string): ?string}> */
    protected static array $customCodecs = [];

    /**
     * Register a custom codec.
     */
    public static function register(string $name, callable $encode, callable $decode): void
    {
        self::$customCodecs[$name] = [
            'encode' => $encode,
            'decode' => $decode,
        ];
    }

    /**
     * Encode a value to binary.
     *
     * @throws InvalidArgumentException
     */
    public static function encode(Ulid|string|null $value, string $format): ?string
    {
        if (blank($value)) {
            return null;
        }

        if (isset(self::$customCodecs[$format])) {
            return (self::$customCodecs[$format]['encode'])($value);
        }

        return match ($format) {
            'uuid' => match (true) {
                self::isBinary($value) => $value,
                default => self::uuidToBinary($value),
            },

            'ulid' => match (true) {
                $value instanceof Ulid => $value->toBinary(),
                self::isBinary($value) => $value,
                default => Ulid::fromString($value)->toBinary(),
            },

            default => throw new InvalidArgumentException("Format [$format] is invalid."),
        };
    }

    /**
     * Decode a binary value to string.
     *
     * @throws InvalidArgumentException
     */
    public static function decode(?string $value, string $format): ?string
    {
        if (blank($value)) {
            return null;
        }

        if (isset(self::$customCodecs[$format])) {
            return (self::$customCodecs[$format]['decode'])($value);
        }

        return match ($format) {
            'uuid' => self::isBinary($value)
                ? self::binaryToUuid($value)
                : self::normalizeUuid($value),

            'ulid' => (
            self::isBinary($value)
                ? Ulid::fromBinary($value)
                : Ulid::fromString($value)
            )->toString(),

            default => throw new InvalidArgumentException("Format [$format] is invalid."),
        };
    }

    /**
     * Get all available format names.
     *
     * @return array<string>
     */
    public static function formats(): array
    {
        return array_unique([
            'uuid',
            'ulid',
            ...array_keys(self::$customCodecs),
        ]);
    }

    /**
     * Determine if the given value is binary data.
     */
    public static function isBinary(mixed $value): bool
    {
        if (! is_string($value) || $value === '') {
            return false;
        }

        if (str_contains($value, "\0")) {
            return true;
        }

        return ! mb_check_encoding($value, 'UTF-8');
    }

    /**
     * Convert UUID string to 16-byte binary.
     */
    private static function uuidToBinary(string $uuid): string
    {
        $uuid = str_replace('-', '', $uuid);

        if (! preg_match('/^[0-9a-f]{32}$/i', $uuid)) {
            throw new InvalidArgumentException('Invalid UUID.');
        }

        $binary = hex2bin($uuid);

        if ($binary === false) {
            throw new InvalidArgumentException('Invalid UUID.');
        }

        return $binary;
    }

    /**
     * Convert 16-byte binary to UUID string.
     */
    private static function binaryToUuid(string $binary): string
    {
        if (strlen($binary) !== 16) {
            throw new InvalidArgumentException('Invalid binary UUID.');
        }

        $hex = bin2hex($binary);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12),
        );
    }

    /**
     * Normalize and validate a UUID string.
     */
    private static function normalizeUuid(string $uuid): string
    {
        if (! preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $uuid
        )) {
            throw new InvalidArgumentException('Invalid UUID.');
        }

        return strtolower($uuid);
    }
}
