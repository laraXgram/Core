<?php

namespace LaraGram\Conversation;

use Closure;

final class Choices
{
    /**
     * The callback data prefix of a choice button.
     *
     * @var string
     */
    public const PREFIX = 'conversation:choice:';

    /**
     * The callback data of the button that ends a multiple choice question.
     *
     * @var string
     */
    public const DONE = 'conversation:choices:done';

    /**
     * @param  array<int|string, string>|\Closure  $options  Value => label pairs.
     * @param  string  $layout  inline | reply
     * @param  int  $columns
     * @param  bool  $multiple
     * @param  string|null  $done  The label of the button finishing a multiple choice.
     * @param  int|null  $min  The fewest selections a multiple choice accepts.
     * @param  int|null  $max  The most selections a multiple choice accepts.
     * @param  string  $mark  The prefix marking a selected option.
     */
    public function __construct(
        public readonly array|Closure $options,
        public readonly string $layout = 'inline',
        public readonly int $columns = 2,
        public readonly bool $multiple = false,
        public readonly ?string $done = null,
        public readonly ?int $min = null,
        public readonly ?int $max = null,
        public readonly string $mark = '✓ ',
    ) {
    }

    /**
     * Get a copy of the choices with the given changes.
     *
     * @param  array<string, mixed>  $attributes
     * @return self
     */
    public function with(array $attributes): self
    {
        return new self(
            $attributes['options'] ?? $this->options,
            $attributes['layout'] ?? $this->layout,
            $attributes['columns'] ?? $this->columns,
            $attributes['multiple'] ?? $this->multiple,
            array_key_exists('done', $attributes) ? $attributes['done'] : $this->done,
            array_key_exists('min', $attributes) ? $attributes['min'] : $this->min,
            array_key_exists('max', $attributes) ? $attributes['max'] : $this->max,
            $attributes['mark'] ?? $this->mark,
        );
    }

    /**
     * Resolve the options, normalising a list into value => label pairs.
     *
     * @param  \LaraGram\Conversation\AnswersBag|null  $answers
     * @return array<int|string, string>
     */
    public function options(?AnswersBag $answers = null): array
    {
        $options = $this->options instanceof Closure
            ? ($this->options)($answers ?? new AnswersBag)
            : $this->options;

        $options = is_array($options) ? $options : iterator_to_array($options);

        // A plain list offers each item as both the value and the label; a map
        // keeps its keys as the values that are stored.
        if (array_is_list($options)) {
            $options = array_combine(
                array_map(strval(...), $options),
                array_map(strval(...), $options)
            );
        }

        $resolved = [];

        foreach ($options as $value => $label) {
            $resolved[$value] = (string) $label;
        }

        return $resolved;
    }

    /**
     * Determine if the given value is one of the options.
     *
     * @param  mixed  $value
     * @param  \LaraGram\Conversation\AnswersBag|null  $answers
     * @return bool
     */
    public function has(mixed $value, ?AnswersBag $answers = null): bool
    {
        return array_key_exists((string) $value, $this->options($answers));
    }

    /**
     * Resolve an incoming update into the value it selected.
     *
     * @param  string|null  $text  The message text, for a reply keyboard.
     * @param  string|null  $callbackData  The callback data, for an inline keyboard.
     * @param  \LaraGram\Conversation\AnswersBag|null  $answers
     * @return int|string|null
     */
    public function resolve(?string $text, ?string $callbackData, ?AnswersBag $answers = null): int|string|null
    {
        $options = $this->options($answers);

        if ($callbackData !== null && str_starts_with($callbackData, self::PREFIX)) {
            $index = (int) substr($callbackData, strlen(self::PREFIX));
            $values = array_keys($options);

            return $values[$index] ?? null;
        }

        if ($text === null) {
            return null;
        }

        $text = trim($text);

        if ($text === '') {
            return null;
        }

        if (array_key_exists($text, $options)) {
            return $text;
        }

        foreach ($options as $value => $label) {
            if ($label === $text || $this->mark.$label === $text) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Determine if the update ends a multiple choice question.
     *
     * @param  string|null  $text
     * @param  string|null  $callbackData
     * @return bool
     */
    public function isDone(?string $text, ?string $callbackData): bool
    {
        if (! $this->multiple) {
            return false;
        }

        if ($callbackData === self::DONE) {
            return true;
        }

        return $this->done !== null && $text !== null && trim($text) === $this->done;
    }

    /**
     * Build the keyboard presenting the options.
     *
     * @param  array<int, int|string>  $selected  The values already selected.
     * @param  \LaraGram\Conversation\AnswersBag|null  $answers
     * @return array<string, mixed>
     */
    public function keyboard(array $selected = [], ?AnswersBag $answers = null): array
    {
        $rows = [];
        $row = [];
        $index = 0;

        foreach ($this->options($answers) as $value => $label) {
            $text = in_array($value, $selected, false) ? $this->mark.$label : $label;

            $row[] = $this->layout === 'inline'
                ? ['text' => $text, 'callback_data' => self::PREFIX.$index]
                : ['text' => $text];

            if (count($row) >= max(1, $this->columns)) {
                $rows[] = $row;
                $row = [];
            }

            $index++;
        }

        if ($row !== []) {
            $rows[] = $row;
        }

        if ($this->multiple && $this->done !== null) {
            $rows[] = [$this->layout === 'inline'
                ? ['text' => $this->done, 'callback_data' => self::DONE]
                : ['text' => $this->done]];
        }

        return $this->layout === 'inline'
            ? ['inline_keyboard' => $rows]
            : ['keyboard' => $rows, 'resize_keyboard' => true, 'one_time_keyboard' => ! $this->multiple];
    }
}
