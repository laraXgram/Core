<?php

namespace LaraGram\Console\Output;

use LaraGram\Console\Exception\InvalidArgumentException;
use LaraGram\Console\Formatter\OutputFormatterInterface;

class TrimmedBufferOutput extends Output
{
    private int $maxLength;
    private string $buffer = '';

    public function __construct(int $maxLength, ?int $verbosity = self::VERBOSITY_NORMAL, bool $decorated = false, ?OutputFormatterInterface $formatter = null)
    {
        if ($maxLength <= 0) {
            throw new InvalidArgumentException(\sprintf('"%s()" expects a strictly positive maxLength. Got %d.', __METHOD__, $maxLength));
        }

        parent::__construct($verbosity, $decorated, $formatter);
        $this->maxLength = $maxLength;
    }

    /**
     * Empties buffer and returns its content.
     */
    public function fetch(): string
    {
        $content = $this->buffer;
        $this->buffer = '';

        return $content;
    }

    protected function doWrite(string $message, bool $newline): void
    {
        $this->buffer .= $message;

        if ($newline) {
            $this->buffer .= \PHP_EOL;
        }

        $this->buffer = substr($this->buffer, -$this->maxLength);
    }
}
