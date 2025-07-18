<?php

namespace LaraGram\Console\Helper;

use LaraGram\Console\Formatter\OutputFormatter;

class FormatterHelper extends Helper
{
    /**
     * Formats a message within a section.
     */
    public function formatSection(string $section, string $message, string $style = 'info'): string
    {
        return \sprintf('<%s>[%s]</%s> %s', $style, $section, $style, $message);
    }

    /**
     * Formats a message as a block of text.
     */
    public function formatBlock(string|array $messages, string $style, bool $large = false): string
    {
        if (!\is_array($messages)) {
            $messages = [$messages];
        }

        $len = 0;
        $lines = [];
        foreach ($messages as $message) {
            $message = OutputFormatter::escape($message);
            $lines[] = \sprintf($large ? '  %s  ' : ' %s ', $message);
            $len = max(self::width($message) + ($large ? 4 : 2), $len);
        }

        $messages = $large ? [str_repeat(' ', $len)] : [];
        for ($i = 0; isset($lines[$i]); ++$i) {
            $messages[] = $lines[$i].str_repeat(' ', $len - self::width($lines[$i]));
        }
        if ($large) {
            $messages[] = str_repeat(' ', $len);
        }

        for ($i = 0; isset($messages[$i]); ++$i) {
            $messages[$i] = \sprintf('<%s>%s</%s>', $style, $messages[$i], $style);
        }

        return implode("\n", $messages);
    }

    /**
     * Truncates a message to the given length.
     */
    public function truncate(string $message, int $length, string $suffix = '...'): string
    {
        $computedLength = $length - self::width($suffix);

        if ($computedLength > self::width($message)) {
            return $message;
        }

        return self::substr($message, 0, $length).$suffix;
    }

    public function getName(): string
    {
        return 'formatter';
    }
}
