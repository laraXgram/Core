<?php

namespace LaraGram\Console\Prompts\Tui;

class Ansi
{
    /**
     * The expression matching a terminal escape sequence.
     */
    protected const SEQUENCE = '/\e\[[0-9;:?<=>]*[ -\/]*[@-~]|\e\][^\x07\e]*(?:\x07|\e\\\\)|\e[@-Z\\\\^_]/';

    public const RESET = "\e[0m";

    /**
     * Remove the escape sequences from the given text.
     */
    public static function strip(string $text): string
    {
        return preg_replace(self::SEQUENCE, '', $text) ?? $text;
    }

    /**
     * Get the number of columns the given text takes up.
     */
    public static function width(string $text): int
    {
        return mb_strwidth(self::strip($text));
    }

    /**
     * Color the text with a hex value such as "#93c5fd".
     */
    public static function color(string $text, ?string $hex): string
    {
        return ($code = self::foreground($hex)) === '' ? $text : $code.$text.self::RESET;
    }

    /**
     * Get the sequence that selects a foreground color given as a hex value.
     */
    public static function foreground(?string $hex): string
    {
        if ($hex === null || ! preg_match('/^#?([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i', $hex, $match)) {
            return '';
        }

        return sprintf("\e[38;2;%d;%d;%dm", hexdec($match[1]), hexdec($match[2]), hexdec($match[3]));
    }

    /**
     * Dim the given text.
     */
    public static function dim(string $text): string
    {
        return "\e[2m".$text.self::RESET;
    }

    /**
     * Make the given text bold.
     */
    public static function bold(string $text): string
    {
        return "\e[1m".$text.self::RESET;
    }

    /**
     * Swap the foreground and background colors of the given text.
     */
    public static function inverse(string $text): string
    {
        return "\e[7m".$text.self::RESET;
    }

    /**
     * Break the text into rows no wider than the given width, carrying the
     * active colors over to the next row.
     *
     * @return list<string>
     */
    public static function wrap(string $text, int $width): array
    {
        $width = max(1, $width);

        if (! str_contains($text, "\e") && mb_strwidth($text) <= $width) {
            return [$text];
        }

        $rows = [];
        $row = '';
        $used = 0;
        $active = '';

        foreach (self::tokens($text) as [$token, $isSequence]) {
            if ($isSequence) {
                $row .= $token;

                if (str_ends_with($token, 'm') && str_starts_with($token, "\e[")) {
                    $active = in_array($token, ["\e[0m", "\e[m"], true) ? '' : $active.$token;
                }

                continue;
            }

            foreach (mb_str_split($token) as $character) {
                $columns = mb_strwidth($character);

                if ($used + $columns > $width && $used > 0) {
                    $rows[] = $active === '' ? $row : $row.self::RESET;
                    $row = $active;
                    $used = 0;
                }

                $row .= $character;
                $used += $columns;
            }
        }

        $rows[] = $row;

        return $rows;
    }

    /**
     * Cut the text down to the given width.
     */
    public static function truncate(string $text, int $width): string
    {
        if ($width <= 0) {
            return '';
        }

        if (self::width($text) <= $width) {
            return $text;
        }

        $result = '';
        $used = 0;

        foreach (self::tokens($text) as [$token, $isSequence]) {
            if ($isSequence) {
                $result .= $token;

                continue;
            }

            foreach (mb_str_split($token) as $character) {
                $columns = mb_strwidth($character);

                if ($used + $columns > $width - 1) {
                    return $result.'…'.self::RESET;
                }

                $result .= $character;
                $used += $columns;
            }
        }

        return $result;
    }

    /**
     * Split the text into its escape sequences and the plain text between them.
     *
     * @return list<array{0: string, 1: bool}>
     */
    protected static function tokens(string $text): array
    {
        $tokens = [];

        foreach (preg_split('/('.substr(self::SEQUENCE, 1, -1).')/', $text, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [] as $index => $part) {
            if ($part !== '') {
                $tokens[] = [$part, $index % 2 === 1];
            }
        }

        return $tokens;
    }
}
