<?php

namespace LaraGram\Console;

use LaraGram\Console\Output\ConsoleOutput;

class BufferedConsoleOutput extends ConsoleOutput
{
    /**
     * The current buffer.
     *
     * @var string
     */
    protected $buffer = '';

    /**
     * Empties the buffer and returns its content.
     *
     * @return string
     */
    public function fetch()
    {
        $buffer = $this->buffer;
        $this->buffer = '';

        return $buffer;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    protected function doWrite(string $message, bool $newline): void
    {
        $this->buffer .= $message;

        if ($newline) {
            $this->buffer .= \PHP_EOL;
        }

        parent::doWrite($message, $newline);
    }
}
