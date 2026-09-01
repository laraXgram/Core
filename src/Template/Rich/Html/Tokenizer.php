<?php

namespace LaraGram\Template\Rich\Html;

/**
 * Turns rich markup into a flat list of {@see Token}s.
 */
final class Tokenizer
{
    /**
     * Matches a single tag, tolerating quoted attribute values containing ">".
     */
    private const TAG = '/<(\/?)([a-zA-Z][a-zA-Z0-9-]*)((?:"[^"]*"|\'[^\']*\'|[^>"\'])*)>/A';

    /**
     * Matches one attribute, with a double quoted, single quoted, bare, or absent value.
     */
    private const ATTRIBUTE = '/([a-zA-Z_:][a-zA-Z0-9_:.-]*)(?:\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'=<>`]*)))?/';

    /**
     * Parse rich markup into tokens.
     *
     * @param  string  $html
     * @return array<int, Token>
     */
    public static function tokenize(string $html): array
    {
        $tokens = [];
        $length = strlen($html);
        $cursor = 0;
        $pending = '';
        $pendingOffset = 0;

        while ($cursor < $length) {
            $next = strpos($html, '<', $cursor);

            if ($next === false) {
                $pending .= substr($html, $cursor);
                break;
            }

            if (! preg_match(self::TAG, $html, $matches, 0, $next)) {
                // Not a tag after all - keep the "<" as literal text.
                $pending .= substr($html, $cursor, $next - $cursor + 1);
                $cursor = $next + 1;

                continue;
            }

            $pending .= substr($html, $cursor, $next - $cursor);

            if ($pending !== '') {
                $tokens[] = new Token(Token::TEXT, text: self::decode($pending), offset: $pendingOffset);
                $pending = '';
            }

            $name = strtolower($matches[2]);
            $trailing = $matches[3];

            if ($matches[1] === '/') {
                $tokens[] = new Token(Token::CLOSE, name: $name, offset: $next);
            } else {
                $selfClosing = str_ends_with(rtrim($trailing), '/');

                $tokens[] = new Token(
                    Token::OPEN,
                    name: $name,
                    attributes: self::attributes($selfClosing ? rtrim(rtrim($trailing), '/') : $trailing),
                    selfClosing: $selfClosing,
                    offset: $next,
                );
            }

            $cursor = $next + strlen($matches[0]);
            $pendingOffset = $cursor;
        }

        if ($pending !== '') {
            $tokens[] = new Token(Token::TEXT, text: self::decode($pending), offset: $pendingOffset);
        }

        return $tokens;
    }

    /**
     * Parse the attribute section of a tag.
     *
     * Values are decoded here and re-encoded on output, so markup keeps working
     * whether the author wrote a literal character or an entity.
     *
     * @param  string  $source
     * @return array<string, string|true>
     */
    private static function attributes(string $source): array
    {
        if (trim($source) === '') {
            return [];
        }

        preg_match_all(self::ATTRIBUTE, $source, $matches, PREG_SET_ORDER);

        $attributes = [];

        foreach ($matches as $match) {
            $name = strtolower($match[1]);

            if ($name === '') {
                continue;
            }

            $value = match (true) {
                isset($match[2]) && $match[2] !== '' => $match[2],
                isset($match[3]) && $match[3] !== '' => $match[3],
                isset($match[4]) && $match[4] !== '' => $match[4],
                // A quoted but empty value is still a value; a missing one is not.
                count($match) > 2 => '',
                default => true,
            };

            $attributes[$name] = is_string($value) ? self::decode($value) : $value;
        }

        return $attributes;
    }

    /**
     * Resolve HTML entities to the characters they stand for.
     *
     * @param  string  $value
     * @return string
     */
    private static function decode(string $value): string
    {
        if (! str_contains($value, '&')) {
            return $value;
        }

        return html_entity_decode($value, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8');
    }
}
