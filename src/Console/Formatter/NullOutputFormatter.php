<?php

namespace LaraGram\Console\Formatter;

final class NullOutputFormatter implements OutputFormatterInterface
{
    private NullOutputFormatterStyle $style;

    public function format(?string $message): ?string
    {
        return null;
    }

    public function getStyle(string $name): OutputFormatterStyleInterface
    {
        // to comply with the interface we must return a OutputFormatterStyleInterface
        return $this->style ??= new NullOutputFormatterStyle();
    }

    public function hasStyle(string $name): bool
    {
        return false;
    }

    public function isDecorated(): bool
    {
        return false;
    }

    public function setDecorated(bool $decorated): void
    {
        // do nothing
    }

    public function setStyle(string $name, OutputFormatterStyleInterface $style): void
    {
        // do nothing
    }
}
