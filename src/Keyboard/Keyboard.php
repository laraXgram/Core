<?php

namespace LaraGram\Keyboard;

use LaraGram\Support\Traits\Macroable;

class Keyboard
{
    use Macroable;

    private string $type;
    private array $keyboard = [];

    public function replyKeyboardMarkup(...$rows): static
    {
        $this->type = 'keyboard';
        $this->keyboard = [
            'keyboard' => $rows
        ];
        return $this;
    }

    public function inlineKeyboardMarkup(...$rows): static
    {
        $this->type = 'inline_keyboard';
        $this->keyboard = [
            'inline_keyboard' => $rows
        ];
        return $this;
    }

    public function replyKeyboardRemove($selective = false): static
    {
        $this->keyboard = [
            'remove_keyboard' => true,
            'selective' => $selective
        ];
        return $this;
    }

    public function forceReply($input_field_placeholder = '', $selective = false): static
    {
        $this->keyboard = [
            'force_reply' => true,
            'input_field_placeholder' => $input_field_placeholder,
            'selective' => $selective
        ];
        return $this;
    }

    public function copyTextButton($text): static
    {
        $this->keyboard = [
            'text' => $text,
        ];
        return $this;
    }

    public function setOption(string $key, mixed $value): static
    {
        $this->keyboard[$key] = $value;
        return $this;
    }

    public function setOptions(array $options): static
    {
        foreach ($options as $key => $value) {
            $this->keyboard[$key] = $value;
        }
        return $this;
    }

    public function addRow(array $row): static
    {
        $this->keyboard[$this->type][] = $row;
        return $this;
    }

    public function removeRow(int $offset): static
    {
        unset($this->keyboard[$this->type][$offset - 1]);
        return $this;
    }

    public function editRow(array $row, int $offset): static
    {
        if ($this->keyboard[$this->type][$offset - 1] != null) {
            $this->keyboard[$this->type][$offset - 1] = $row;
        }
        return $this;
    }

    public function addCol(array $col, int|null $rowIndex = null): static
    {
        $rowIndex = $rowIndex ?? count($this->keyboard[$this->type]);

        $this->keyboard[$this->type][$rowIndex - 1][] = $col;

        return $this;
    }

    public function removeCol(int $rowIndex, int $offset): static
    {
        unset($this->keyboard[$this->type][$rowIndex - 1][$offset - 1]);
        return $this;
    }

    public function editCol(array $row, int $rowIndex, int $offset): static
    {
        if ($this->keyboard[$this->type][$rowIndex - 1][$offset - 1] != null) {
            $this->keyboard[$this->type][$rowIndex - 1][$offset - 1] = $row;
        }
        return $this;
    }

    public function get(bool $array = false): array|string|false
    {
        if ($array) {
            return $this->keyboard;
        }
        return json_encode($this->keyboard);
    }
}