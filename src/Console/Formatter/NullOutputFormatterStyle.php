<?php

namespace LaraGram\Console\Formatter;

final class NullOutputFormatterStyle implements OutputFormatterStyleInterface
{
    public function apply(string $text): string
    {
        return $text;
    }

    public function setBackground(?string $color): void
    {
        // do nothing
    }

    public function setForeground(?string $color): void
    {
        // do nothing
    }

    public function setOption(string $option): void
    {
        // do nothing
    }

    public function setOptions(array $options): void
    {
        // do nothing
    }

    public function unsetOption(string $option): void
    {
        // do nothing
    }
}
