<?php

namespace LaraGram\Console\Prompts\Output;

use LaraGram\Console\Output\ConsoleOutput as LaraGramConsoleOutput;

class ConsoleOutput extends LaraGramConsoleOutput
{
    /**
     * How many new lines were written by the last output.
     */
    protected int $newLinesWritten = 1;

    /**
     * How many new lines were written by the last output.
     */
    public function newLinesWritten(): int
    {
        return $this->newLinesWritten;
    }

    /**
     * Write the output and capture the number of trailing new lines.
     */
    protected function doWrite(string $message, bool $newline): void
    {
        parent::doWrite($message, $newline);

        if ($newline) {
            $message .= \PHP_EOL;
        }

        $trailingNewLines = strlen($message) - strlen(rtrim($message, \PHP_EOL));

        if (trim($message) === '') {
            $this->newLinesWritten += $trailingNewLines;
        } else {
            $this->newLinesWritten = $trailingNewLines;
        }
    }

    /**
     * Write output directly, bypassing newline capture.
     */
    public function writeDirectly(string $message): void
    {
        parent::doWrite($message, false);
    }
}
