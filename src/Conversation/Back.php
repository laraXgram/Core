<?php

namespace LaraGram\Conversation;

use LaraGram\Keyboard\Keyboard;

final class Back
{
    public function __construct(
        public ?bool $enabled = null,
        public ?string $mode = null,
        public ?string $label = null,
        public ?string $callbackData = null,
        public ?string $command = null,
    ) {
    }

    /**
     * Build a back control (unset arguments inherit from the level below).
     *
     * @param  string|null  $mode          reply | inline | command | text | none
     * @param  string|null  $label         Button text / matched text.
     * @param  string|null  $callbackData  Callback data for inline mode.
     * @param  string|null  $command       Command that triggers back.
     * @param  bool|null    $enabled
     * @return self
     */
    public static function make(
        ?string $mode = null,
        ?string $label = null,
        ?string $callbackData = null,
        ?string $command = null,
        ?bool $enabled = null,
    ): self {
        return new self($enabled, $mode, $label, $callbackData, $command);
    }

    /**
     * A disabled back control.
     *
     * @return self
     */
    public static function disabled(): self
    {
        return new self(enabled: false);
    }

    /**
     * Resolve the effective control from the question and conversation levels,
     * filling any unset field from the built-in default.
     *
     * @param  \LaraGram\Conversation\Back|null  $question
     * @param  \LaraGram\Conversation\Back|null  $global
     * @return self
     */
    public static function resolve(?Back $question, ?Back $global): self
    {
        $default = self::defaults();

        return new self(
            enabled: $question?->enabled ?? $global?->enabled ?? $default->enabled,
            mode: $question?->mode ?? $global?->mode ?? $default->mode,
            label: $question?->label ?? $global?->label ?? $default->label,
            callbackData: $question?->callbackData ?? $global?->callbackData ?? $default->callbackData,
            command: $question?->command ?? $global?->command ?? $default->command,
        );
    }

    /**
     * The built-in default back control (final fallback). Not configurable.
     * define back at the conversation or question level instead.
     *
     * @return self
     */
    public static function defaults(): self
    {
        return new self(
            enabled: true,
            mode: 'reply',
            label: 'Back',
            callbackData: 'conversation:back',
            command: null,
        );
    }

    /**
     * Determine whether an incoming update is a back request.
     *
     * @param  string|null  $text
     * @param  string|null  $callbackData
     * @return bool
     */
    public function matches(?string $text, ?string $callbackData): bool
    {
        if (! $this->enabled) {
            return false;
        }

        if ($callbackData !== null && $callbackData === $this->callbackData) {
            return true;
        }

        if ($text !== null) {
            $text = trim($text);

            if ($text !== '' && $text === $this->label) {
                return true;
            }

            if ($this->command !== null
                && ($text === $this->command || $text === '/'.ltrim($this->command, '/'))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Produce the reply markup (JSON) for a prompt, injecting the back button
     * where appropriate. Adapts the button type to the existing keyboard.
     *
     * @param  mixed  $existing  Keyboard|array|string(json)|null
     * @return string|null
     */
    public function markup(mixed $existing): ?string
    {
        $keyboard = $this->normalize($existing);

        // Modes that never inject a button.
        if (! $this->enabled || in_array($this->mode, ['command', 'text', 'none'], true)) {
            return $keyboard === null ? null : json_encode($keyboard);
        }

        // Attach the back button to whatever keyboard the question already has.
        $inject = $this->mode;

        if ($keyboard !== null) {
            if (isset($keyboard['inline_keyboard'])) {
                $inject = 'inline';
            } elseif (isset($keyboard['keyboard'])) {
                $inject = 'reply';
            }
        }

        $row = $inject === 'inline'
            ? [['text' => $this->label, 'callback_data' => $this->callbackData]]
            : [['text' => $this->label]];

        if ($keyboard === null) {
            $keyboard = $inject === 'inline'
                ? ['inline_keyboard' => [$row]]
                : ['keyboard' => [$row], 'resize_keyboard' => true];
        } elseif ($inject === 'inline') {
            $keyboard['inline_keyboard'][] = $row;
        } else {
            $keyboard['keyboard'][] = $row;
        }

        return json_encode($keyboard);
    }

    /**
     * Normalize a keyboard value to an array.
     *
     * @param  mixed  $existing
     * @return array|null
     */
    protected function normalize(mixed $existing): ?array
    {
        if ($existing === null) {
            return null;
        }

        if ($existing instanceof Keyboard) {
            return $existing->get(true);
        }

        if (is_array($existing)) {
            return $existing;
        }

        if (is_string($existing)) {
            $decoded = json_decode($existing, true);

            return is_array($decoded) ? $decoded : null;
        }

        if (is_object($existing) && method_exists($existing, 'get')) {
            $value = $existing->get(true);

            return is_array($value) ? $value : null;
        }

        return null;
    }
}
