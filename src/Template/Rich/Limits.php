<?php

namespace LaraGram\Template\Rich;

/**
 * The limits Telegram applies to a rich message.
 *
 * @see https://core.telegram.org/bots/api#rich-message-limits
 */
final class Limits
{
    /**
     * Maximum number of UTF-8 characters in the rich message text, including
     * custom emoji alternative text and formula source.
     */
    public const TEXT_LENGTH = 32768;

    /**
     * Maximum number of blocks, including nested blocks, list items, ordered
     * list items, table rows, quotation blocks and details blocks.
     */
    public const BLOCKS = 500;

    /**
     * Maximum number of nested formatting and block levels.
     */
    public const NESTING = 16;

    /**
     * Maximum number of media attachments in a single rich message.
     */
    public const MEDIA = 50;

    /**
     * Maximum number of columns in a table.
     */
    public const TABLE_COLUMNS = 20;

    /**
     * Minimum number of buttons in a button row.
     */
    public const ROW_BUTTONS_MIN = 1;

    /**
     * Maximum number of buttons in a button row.
     */
    public const ROW_BUTTONS_MAX = 8;

    /**
     * Maximum length of a media identifier used in a tg:// link.
     */
    public const MEDIA_ID_LENGTH = 64;
}
