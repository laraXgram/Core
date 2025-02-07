<?php

namespace LaraGram\Console\Prompts\Concerns;

use LaraGram\Console\Prompts\Output\BufferedConsoleOutput;
use LaraGram\Console\Prompts\Terminal;
use RuntimeException;

trait FakesInputOutput
{
    /**
     * Fake the terminal and queue key presses to be simulated.
     *
     * @param  array<string>  $keys
     */
    public static function fake(array $keys = []): void
    {
        // Force interactive mode when testing because we will be mocking the terminal.
        static::interactive();

        self::setOutput(new BufferedConsoleOutput);
    }

    /**
     * Get the buffered console output.
     */
    public static function content(): string
    {
        if (! static::output() instanceof BufferedConsoleOutput) {
            throw new RuntimeException('Prompt must be faked before accessing content.');
        }

        return static::output()->content();
    }

    /**
     * Get the buffered console output, stripped of escape sequences.
     */
    public static function strippedContent(): string
    {
        return preg_replace("/\e\[[0-9;?]*[A-Za-z]/", '', static::content());
    }
}
