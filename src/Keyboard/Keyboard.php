<?php

namespace LaraGram\Keyboard;

class Keyboard
{
    /**
     * Language codes whose script is written right-to-left.
     *
     * @var array<int, string>
     */
    public static array $rightToLeftLocales = [
        'ar',   // Arabic
        'arc',  // Aramaic
        'azb',  // South Azerbaijani
        'bal',  // Balochi
        'bqi',  // Bakhtiari
        'ckb',  // Central Kurdish (Sorani)
        'dv',   // Divehi
        'fa',   // Persian
        'glk',  // Gilaki
        'he',   // Hebrew
        'iw',   // Hebrew (legacy code)
        'ji',   // Yiddish (legacy code)
        'khw',  // Khowar
        'ks',   // Kashmiri
        'ku',   // Kurdish
        'lrc',  // Northern Luri
        'mzn',  // Mazanderani
        'nqo',  // N'Ko
        'pnb',  // Western Punjabi
        'prd',  // Parsi-Dari
        'ps',   // Pashto
        'sd',   // Sindhi
        'skr',  // Saraiki
        'syr',  // Syriac
        'ug',   // Uyghur
        'ur',   // Urdu
        'yi',   // Yiddish
    ];

    /**
     * Whether keyboards default to right-to-left when the application locale is
     * written in a right-to-left script.
     *
     * @var bool
     */
    public static bool $autoRightToLeft = true;

    protected $type;
    protected $keyboard = [];

    /**
     * Explicit direction for this keyboard, or null to follow the locale.
     *
     * @var bool|null
     */
    protected ?bool $rtl = null;

    /**
     * This object represents a custom keyboard with reply options.
     * Not supported in channels and for messages sent on behalf of a Telegram Business account.
     *
     * @param ...$rows
     * @return $this
     */
    public function replyKeyboardMarkup(...$rows)
    {
        $this->type = 'keyboard';
        $this->keyboard = [
            'keyboard' => $rows,
            'resize_keyboard' => true,
        ];

        return $this;
    }

    /**
     * Upon receiving a message with this object, Telegram clients will remove the current custom keyboard and display the default letter-keyboard.
     * By default, custom keyboards are displayed until a new keyboard is sent by a bot.
     * An exception is made for one-time keyboards that are hidden immediately after the user presses a button (see ReplyKeyboardMarkup).
     * Not supported in channels and for messages sent on behalf of a Telegram Business account.
     *
     * @param bool $selective
     * @return $this
     */
    public function replyKeyboardRemove($selective = false)
    {
        $this->type = 'keyboard';
        $this->keyboard = [
            'remove_keyboard' => true,
            'selective' => $selective
        ];

        return $this;
    }

    /**
     * This object represents an inline keyboard that appears right next to the message it belongs to.
     *
     * @param mixed ...$rows
     * @return $this
     */
    public function inlineKeyboardMarkup(...$rows)
    {
        $this->type = 'inline_keyboard';
        $this->keyboard = [
            'inline_keyboard' => $rows
        ];

        return $this;
    }

    /**
     * Upon receiving a message with this object, Telegram clients will display a reply interface to the user (act as if the user has selected the bot's message and tapped 'Reply').
     * This can be extremely useful if you want to create user-friendly step-by-step interfaces without having to sacrifice privacy mode.
     * Not supported in channels and for messages sent on behalf of a Telegram Business account.
     *
     * @param string $input_field_placeholder
     * @param bool $selective
     * @return $this
     */
    public function forceReply(string $input_field_placeholder = '', bool $selective = false)
    {
        $this->type = 'keyboard';
        $this->keyboard = [
            'force_reply' => true,
            'input_field_placeholder' => $input_field_placeholder,
            'selective' => $selective
        ];

        return $this;
    }

    /**
     * Show the keyboard inside a reply interface, as if the user had manually
     * selected the bot's message and tapped 'Reply'.
     * Supported by both `replyKeyboardMarkup` and `inlineKeyboardMarkup`.
     *
     * @param bool $force_reply
     * @return $this
     */
    public function withForceReply(bool $force_reply = true)
    {
        $this->keyboard['force_reply'] = $force_reply;

        return $this;
    }

    /**
     * The option can be `is_persistent` | `resize_keyboard` | `one_time_keyboard` | `input_field_placeholder` | `selective`.
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setOption(string $key, mixed $value)
    {
        $this->keyboard[$key] = $value;
        return $this;
    }

    /**
     * The options can be an array of `is_persistent` | `resize_keyboard` | `one_time_keyboard` | `input_field_placeholder` | `selective`.
     *
     * @param array $options
     * @return $this
     */
    public function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            $this->keyboard[$key] = $value;
        }

        return $this;
    }

    /**
     * Add a new row to keyboard. You can use Make::row() or row().
     *
     * @param array $row
     * @return $this
     */
    public function appendRow(array $row)
    {
        $this->keyboard[$this->type][] = $row;

        return $this;
    }

    /**
     * Prepend a new row to keyboard. You can use Make::row() or row().
     *
     * @param array $row
     * @return $this
     */
    public function prependRow(array $row)
    {
        array_unshift($this->keyboard[$this->type], $row);

        return $this;
    }

    /**
     * Add a new row to keyboard. You can use Make::row() or row().
     *
     * @param array $row
     * @return $this
     *
     * @deprecated Use `appendRow`
     */
    public function addRow(array $row)
    {
        return $this->appendRow($row);
    }

    /**
     * Delete a row based on offset.
     *
     * @param int $offset
     * @return $this
     */
    public function removeRow(int $offset)
    {
        unset($this->keyboard[$this->type][$offset - 1]);

        return $this;
    }

    /**
     * Edit a row based on offset.
     *
     * @param array $row
     * @param int $offset
     * @return $this
     */
    public function editRow(array $row, int $offset)
    {
        if ($this->keyboard[$this->type][$offset - 1] != null) {
            $this->keyboard[$this->type][$offset - 1] = $row;
        }

        return $this;
    }

    /**
     * Add a new col to keyboard. You can use `Make` class.
     *
     * @param array $col
     * @param int|null $rowIndex
     * @return $this
     */
    public function appendCol(array $col, int|null $rowIndex = null)
    {
        $rowIndex = $rowIndex ?? count($this->keyboard[$this->type]);

        $this->keyboard[$this->type][$rowIndex - 1][] = $col;

        return $this;
    }

    /**
     * Prepend a new col to keyboard. You can use `Make` class.
     *
     * @param array $col
     * @param int|null $rowIndex
     * @return $this
     */
    public function prependCol(array $col, int|null $rowIndex = null)
    {
        $rowIndex = $rowIndex ?? count($this->keyboard[$this->type]);

        if (!isset($this->keyboard[$this->type][$rowIndex - 1])) {
            $this->keyboard[$this->type][$rowIndex - 1] = [];
        }

        array_unshift($this->keyboard[$this->type][$rowIndex - 1], $col);

        return $this;
    }

    /**
     * Add a new col to keyboard. You can use `Make` class.
     *
     * @param array $col
     * @param int|null $rowIndex
     * @return $this
     *
     * @deprecated Use `appendCol`
     */
    public function addCol(array $col, int|null $rowIndex = null)
    {
        return $this->appendCol($col, $rowIndex);
    }

    /**
     * Delete a col based on index and offset.
     *
     * @param int $rowIndex
     * @param int $offset
     * @return $this
     */
    public function removeCol(int $rowIndex, int $offset)
    {
        unset($this->keyboard[$this->type][$rowIndex - 1][$offset - 1]);

        return $this;
    }

    /**
     * Edit a col based on index and offset.
     *
     * @param array $row
     * @param int $rowIndex
     * @param int $offset
     * @return $this
     */
    public function editCol(array $row, int $rowIndex, int $offset)
    {
        if ($this->keyboard[$this->type][$rowIndex - 1][$offset - 1] != null) {
            $this->keyboard[$this->type][$rowIndex - 1][$offset - 1] = $row;
        }

        return $this;
    }

    /**
     * Reverse the columns of every row, for right to left keyboards.
     *
     * @param bool $rtl
     * @return $this
     */
    public function rightToLeft(bool $rtl = true)
    {
        $this->rtl = $rtl;

        return $this;
    }

    /**
     * Keep the columns in their declared order, overriding the locale default.
     *
     * @return $this
     */
    public function leftToRight()
    {
        $this->rtl = false;

        return $this;
    }

    /**
     * Determine whether this keyboard renders right-to-left.
     *
     * @return bool
     */
    public function isRightToLeft(): bool
    {
        return $this->rtl ?? static::localeIsRightToLeft();
    }

    /**
     * Determine whether the current application locale is written right-to-left.
     *
     * @return bool
     */
    public static function localeIsRightToLeft(): bool
    {
        if (! static::$autoRightToLeft || ! function_exists('app')) {
            return false;
        }

        try {
            $locale = app()->getLocale();
        } catch (\Throwable) {
            return false;
        }

        if (! is_string($locale) || $locale === '') {
            return false;
        }

        $language = strtolower(preg_split('/[-_.@]/', $locale, 2)[0]);

        return in_array($language, static::$rightToLeftLocales, true);
    }

    /**
     * Get an array or Json of the keyboard.
     *
     * @param bool $array
     * @return array|string
     */
    public function get(bool $array = false)
    {
        $keyboard = $this->keyboard;

        if ($this->isRightToLeft() && isset($keyboard[$this->type]) && is_array($keyboard[$this->type])) {
            $keyboard[$this->type] = array_map(
                fn ($row) => is_array($row) ? array_values(array_reverse($row)) : $row,
                array_values($keyboard[$this->type])
            );
        }

        if ($array) {
            return $keyboard;
        }

        return json_encode($keyboard);
    }
}
