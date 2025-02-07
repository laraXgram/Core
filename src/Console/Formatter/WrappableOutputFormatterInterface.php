<?php

namespace LaraGram\Console\Formatter;

interface WrappableOutputFormatterInterface extends OutputFormatterInterface
{
    /**
     * Formats a message according to the given styles, wrapping at `$width` (0 means no wrapping).
     */
    public function formatAndWrap(?string $message, int $width): string;
}
