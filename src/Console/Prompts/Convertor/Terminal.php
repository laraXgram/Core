<?php

declare(strict_types=1);

namespace LaraGram\Console\Prompts\Convertor;

use LaraGram\Console\Terminal as ConsoleTerminal;

/**
 * @internal
 */
final class Terminal
{
    /**
     * An instance of LaraGram's console terminal.
     */
    private ConsoleTerminal $terminal;

    /**
     * Creates a new terminal instance.
     */
    public function __construct(?ConsoleTerminal $terminal = null)
    {
        $this->terminal = $terminal ?? new ConsoleTerminal;
    }

    /**
     * Gets the terminal width.
     */
    public function width(): int
    {
        return $this->terminal->getWidth();
    }

    /**
     * Gets the terminal height.
     */
    public function height(): int
    {
        return $this->terminal->getHeight();
    }

    /**
     * Clears the terminal screen.
     */
    public function clear(): void
    {
        Convertor::getRenderer()->write("\ec");
    }
}
