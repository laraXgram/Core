<?php

namespace LaraGram\Console\Formatter;

interface OutputFormatterStyleInterface
{
    /**
     * Sets style foreground color.
     */
    public function setForeground(?string $color): void;

    /**
     * Sets style background color.
     */
    public function setBackground(?string $color): void;

    /**
     * Sets some specific style option.
     */
    public function setOption(string $option): void;

    /**
     * Unsets some specific style option.
     */
    public function unsetOption(string $option): void;

    /**
     * Sets multiple style options at once.
     */
    public function setOptions(array $options): void;

    /**
     * Applies the style to a given text.
     */
    public function apply(string $text): string;
}
