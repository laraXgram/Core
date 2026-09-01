<?php

namespace LaraGram\Template\Rich\Html;

/**
 * The tag vocabulary of rich messages.
 *
 * Everything Telegram understands is listed in {@see self::SUPPORTED}; the
 * simplified spellings LaraGram adds on top are in {@see self::ALIASES}.
 *
 * @see https://core.telegram.org/bots/api#rich-html-style
 */
final class Tags
{
    /**
     * Tags Telegram accepts in the `html` field of an InputRichMessage.
     *
     * @var array<int, string>
     */
    public const SUPPORTED = [
        // Inline formatting.
        'b', 'strong', 'i', 'em', 'u', 'ins', 's', 'strike', 'del',
        'code', 'mark', 'sub', 'sup', 'a', 'br',
        'tg-spoiler', 'tg-reference', 'tg-emoji', 'tg-time', 'tg-math',

        // Structure.
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'p', 'pre', 'footer', 'hr', 'ul', 'ol', 'li', 'input',
        'blockquote', 'aside', 'cite',
        'figure', 'figcaption',
        'table', 'caption', 'tr', 'th', 'td',
        'details', 'summary',
        'tg-math-block',

        // Media.
        'img', 'video', 'audio', 'tg-document', 'tg-map',
        'tg-collage', 'tg-slideshow',

        // Buttons.
        'tg-button', 'tg-button-row',

        // Drafts only.
        'tg-thinking',
    ];

    /**
     * Simplified tag names mapped to the Telegram tag they compile to.
     *
     * `math` and `btn` are handled separately because their output depends on
     * their attributes, but they are listed here so they resolve as known tags.
     *
     * @var array<string, string>
     */
    public const ALIASES = [
        'spoiler'   => 'tg-spoiler',
        'emoji'     => 'tg-emoji',
        'time'      => 'tg-time',
        'ref'       => 'tg-reference',
        'doc'       => 'tg-document',
        'map'       => 'tg-map',
        'collage'   => 'tg-collage',
        'slideshow' => 'tg-slideshow',
        'thinking'  => 'tg-thinking',
        'row'       => 'tg-button-row',
        'math'      => 'tg-math',
        'btn'       => 'tg-button',
    ];

    /**
     * Attribute renames applied per simplified tag.
     *
     * @var array<string, array<string, string>>
     */
    public const ATTRIBUTE_ALIASES = [
        'tg-emoji' => ['id' => 'emoji-id'],
        'tg-map'   => ['lon' => 'long', 'lng' => 'long'],
    ];

    /**
     * Tags that never have a closing tag.
     *
     * @var array<int, string>
     */
    public const VOID = [
        'br', 'hr', 'img', 'input', 'tg-map',
    ];

    /**
     * Tags that introduce a block-level boundary, used to decide where
     * insignificant whitespace may be dropped.
     *
     * @var array<int, string>
     */
    public const BLOCK_LEVEL = [
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'p', 'pre', 'footer', 'hr', 'ul', 'ol', 'li',
        'blockquote', 'aside', 'figure', 'figcaption',
        'table', 'caption', 'tr', 'th', 'td',
        'details', 'summary',
        'tg-math-block', 'tg-button-row',
        'tg-collage', 'tg-slideshow', 'tg-map', 'tg-thinking',
        'img', 'video', 'audio', 'tg-document',
    ];

    /**
     * Tags that count towards the 500 block limit.
     *
     * @var array<int, string>
     */
    public const COUNTS_AS_BLOCK = [
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'p', 'pre', 'footer', 'hr', 'ul', 'ol', 'li',
        'blockquote', 'aside', 'figure',
        'table', 'tr',
        'details',
        'tg-math-block', 'tg-button-row',
        'tg-collage', 'tg-slideshow', 'tg-map', 'tg-thinking',
        'img', 'video', 'audio', 'tg-document',
    ];

    /**
     * Tags whose contents keep their whitespace exactly as written.
     *
     * @var array<int, string>
     */
    public const PRESERVES_WHITESPACE = [
        'pre', 'code', 'tg-math', 'tg-math-block',
    ];

    /**
     * Media tags that accept the caption/credit/spoiler attribute shorthand.
     *
     * @var array<int, string>
     */
    public const CAPTIONABLE = [
        'img', 'video', 'audio', 'tg-document', 'tg-map',
    ];

    /**
     * Tags that may only be used in a message sent with sendRichMessageDraft.
     *
     * @var array<int, string>
     */
    public const DRAFT_ONLY = [
        'tg-thinking',
    ];

    /**
     * Determine whether a tag never has a closing tag.
     *
     * @param  string  $tag
     * @return bool
     */
    public static function isVoid(string $tag): bool
    {
        return in_array($tag, self::VOID, true);
    }

    /**
     * Determine whether a tag introduces a block-level boundary.
     *
     * @param  string  $tag
     * @return bool
     */
    public static function isBlockLevel(string $tag): bool
    {
        return in_array($tag, self::BLOCK_LEVEL, true);
    }

    /**
     * Determine whether a tag is supported by Telegram.
     *
     * @param  string  $tag
     * @return bool
     */
    public static function isSupported(string $tag): bool
    {
        return in_array($tag, self::SUPPORTED, true);
    }
}
