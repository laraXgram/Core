<?php

namespace LaraGram\Console\Helper;

use LaraGram\Console\Exception\InvalidArgumentException;

class HelperSet implements \IteratorAggregate
{
    /** @var array<string, HelperInterface> */
    private array $helpers = [];

    /**
     * @param HelperInterface[] $helpers
     */
    public function __construct(array $helpers = [])
    {
        foreach ($helpers as $alias => $helper) {
            $this->set($helper, \is_int($alias) ? null : $alias);
        }
    }

    public function set(HelperInterface $helper, ?string $alias = null): void
    {
        $this->helpers[$helper->getName()] = $helper;
        if (null !== $alias) {
            $this->helpers[$alias] = $helper;
        }

        $helper->setHelperSet($this);
    }

    /**
     * Returns true if the helper if defined.
     */
    public function has(string $name): bool
    {
        return isset($this->helpers[$name]);
    }

    /**
     * Gets a helper value.
     *
     * @throws InvalidArgumentException if the helper is not defined
     */
    public function get(string $name): HelperInterface
    {
        if (!$this->has($name)) {
            throw new InvalidArgumentException(\sprintf('The helper "%s" is not defined.', $name));
        }

        return $this->helpers[$name];
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->helpers);
    }
}
