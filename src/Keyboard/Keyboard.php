<?php

namespace LaraGram\Keyboard;

class Keyboard
{
    protected $type;
    protected $keyboard = [];

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
    public function replyKeyboardRemove($selective = false): static
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
    public function forceReply(string $input_field_placeholder = '', bool $selective = false): static
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
     * The option can be `is_persistent` | `resize_keyboard` | `one_time_keyboard` | `input_field_placeholder` | `selective`.
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setOption(string $key, mixed $value): static
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
    public function setOptions(array $options): static
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
    public function appendRow(array $row): static
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
    public function prependRow(array $row): static
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
    public function addRow(array $row): static
    {
        return $this->appendRow($row);
    }

    /**
     * Delete a row based on offset.
     *
     * @param int $offset
     * @return $this
     */
    public function removeRow(int $offset): static
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
    public function editRow(array $row, int $offset): static
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
    public function appendCol(array $col, int|null $rowIndex = null): static
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
    public function prependCol(array $col, int|null $rowIndex = null): static
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
    public function addCol(array $col, int|null $rowIndex = null): static
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
    public function removeCol(int $rowIndex, int $offset): static
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
    public function editCol(array $row, int $rowIndex, int $offset): static
    {
        if ($this->keyboard[$this->type][$rowIndex - 1][$offset - 1] != null) {
            $this->keyboard[$this->type][$rowIndex - 1][$offset - 1] = $row;
        }

        return $this;
    }

    /**
     * Get an array or Json of the keyboard.
     *
     * @param bool $array
     * @return array|string
     */
    public function get(bool $array = false)
    {
        if ($array) {
            return $this->keyboard;
        }

        return json_encode($this->keyboard);
    }
}
