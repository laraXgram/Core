<?php

namespace LaraGram\Console\CommandLoader;

use LaraGram\Console\Command\Command;
use LaraGram\Console\Exception\CommandNotFoundException;

class ContainerCommandLoader implements CommandLoaderInterface
{
    /**
     * @param array $commandMap An array with command names as keys and service ids as values
     */
    public function __construct(
        private $container,
        private array $commandMap,
    ) {
    }

    public function get(string $name): Command
    {
        if (!$this->has($name)) {
            throw new CommandNotFoundException(\sprintf('Command "%s" does not exist.', $name));
        }

        return $this->container->get($this->commandMap[$name]);
    }

    public function has(string $name): bool
    {
        return isset($this->commandMap[$name]) && $this->container->has($this->commandMap[$name]);
    }

    public function getNames(): array
    {
        return array_keys($this->commandMap);
    }
}
