<?php

namespace LaraGram\Console\CommandLoader;

use LaraGram\Console\Command\Command;
use LaraGram\Console\Exception\CommandNotFoundException;

interface CommandLoaderInterface
{
    /**
     * Loads a command.
     *
     * @throws CommandNotFoundException
     */
    public function get(string $name): Command;

    /**
     * Checks if a command exists.
     */
    public function has(string $name): bool;

    /**
     * @return string[]
     */
    public function getNames(): array;
}
