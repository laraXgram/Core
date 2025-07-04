<?php

namespace LaraGram\Listening;

use InvalidArgumentException;
use LaraGram\Listening\Exceptions\ListenCircularReferenceException;

class BaseListenCollection implements \IteratorAggregate, \Countable
{
    /**
     * @var array<string, BaseListen>
     */
    private array $listens = [];

    /**
     * @var array<string, Alias>
     */
    private array $aliases = [];

    /**
     * @var array<string, int>
     */
    private array $priorities = [];

    public function __clone()
    {
        foreach ($this->listens as $name => $listen) {
            $this->listens[$name] = clone $listen;
        }

        foreach ($this->aliases as $name => $alias) {
            $this->aliases[$name] = clone $alias;
        }
    }

    /**
     * Gets the current ListenCollection as an Iterator that includes all listens.
     *
     * It implements \IteratorAggregate.
     *
     * @see all()
     *
     * @return \ArrayIterator<string, BaseListen>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->all());
    }

    /**
     * Gets the number of Listens in this collection.
     */
    public function count(): int
    {
        return \count($this->listens);
    }

    public function add(string $name, BaseListen $listen, int $priority = 0): void
    {
        unset($this->listens[$name], $this->priorities[$name], $this->aliases[$name]);

        $this->listens[$name] = $listen;

        if ($priority) {
            $this->priorities[$name] = $priority;
        }
    }

    /**
     * Returns all listens in this collection.
     *
     * @return array<string, BaseListen>
     */
    public function all(): array
    {
        if ($this->priorities) {
            $priorities = $this->priorities;
            $keysOrder = array_flip(array_keys($this->listens));
            uksort($this->listens, static fn ($n1, $n2) => (($priorities[$n2] ?? 0) <=> ($priorities[$n1] ?? 0)) ?: ($keysOrder[$n1] <=> $keysOrder[$n2]));
        }

        return $this->listens;
    }

    /**
     * Gets a listen by name.
     */
    public function get(string $name): ?BaseListen
    {
        $visited = [];
        while (null !== $alias = $this->aliases[$name] ?? null) {
            if (false !== $searchKey = array_search($name, $visited)) {
                $visited[] = $name;

                throw new ListenCircularReferenceException($name, \array_slice($visited, $searchKey));
            }

            if ($alias->isDeprecated()) {
                $deprecation = $alias->getDeprecation($name);

                trigger_error(($deprecation['package'] || $deprecation['version'] ? "Since {$deprecation['package']} {$deprecation['version']}: " : '').$deprecation['message'], \E_USER_DEPRECATED);
            }

            $visited[] = $name;
            $name = $alias->getId();
        }

        return $this->listens[$name] ?? null;
    }

    /**
     * Removes a listen or an array of listens by name from the collection.
     *
     * @param string|string[] $name The listen name or an array of listen names
     */
    public function remove(string|array $name): void
    {
        $listens = [];
        foreach ((array) $name as $n) {
            if (isset($this->listens[$n])) {
                $listens[] = $n;
            }

            unset($this->listens[$n], $this->priorities[$n], $this->aliases[$n]);
        }

        if (!$listens) {
            return;
        }

        foreach ($this->aliases as $k => $alias) {
            if (\in_array($alias->getId(), $listens, true)) {
                unset($this->aliases[$k]);
            }
        }
    }

    /**
     * Adds a listen collection at the end of the current set by appending all
     * listens of the added collection.
     */
    public function addCollection(self $collection): void
    {
        // we need to remove all listens with the same names first because just replacing them
        // would not place the new listen at the end of the merged array
        foreach ($collection->all() as $name => $listen) {
            unset($this->listens[$name], $this->priorities[$name], $this->aliases[$name]);
            $this->listens[$name] = $listen;

            if (isset($collection->priorities[$name])) {
                $this->priorities[$name] = $collection->priorities[$name];
            }
        }

        foreach ($collection->getAliases() as $name => $alias) {
            unset($this->listens[$name], $this->priorities[$name], $this->aliases[$name]);

            $this->aliases[$name] = $alias;
        }
    }

    /**
     * Adds a prefix to the path of all child listens.
     */
    public function addPrefix(string $prefix, array $defaults = [], array $requirements = []): void
    {
        if ('' === $prefix) {
            return;
        }

        foreach ($this->listens as $listen) {
            $listen->setPattern($prefix.$listen->getPattern());
            $listen->addDefaults($defaults);
            $listen->addRequirements($requirements);
        }
    }

    /**
     * Adds a prefix to the name of all the listens within in the collection.
     */
    public function addNamePrefix(string $prefix): void
    {
        $prefixedListens = [];
        $prefixedPriorities = [];
        $prefixedAliases = [];

        foreach ($this->listens as $name => $listen) {
            $prefixedListens[$prefix.$name] = $listen;
            if (null !== $canonicalName = $listen->getDefault('_canonical_listen')) {
                $listen->setDefault('_canonical_listen', $prefix.$canonicalName);
            }
            if (isset($this->priorities[$name])) {
                $prefixedPriorities[$prefix.$name] = $this->priorities[$name];
            }
        }

        foreach ($this->aliases as $name => $alias) {
            $prefixedAliases[$prefix.$name] = $alias->withId($prefix.$alias->getId());
        }

        $this->listens = $prefixedListens;
        $this->priorities = $prefixedPriorities;
        $this->aliases = $prefixedAliases;
    }

    /**
     * Sets a condition on all listens.
     *
     * Existing conditions will be overridden.
     */
    public function setCondition(?string $condition): void
    {
        foreach ($this->listens as $listen) {
            $listen->setCondition($condition);
        }
    }

    /**
     * Adds defaults to all listens.
     *
     * An existing default value under the same name in a listen will be overridden.
     */
    public function addDefaults(array $defaults): void
    {
        if ($defaults) {
            foreach ($this->listens as $listen) {
                $listen->addDefaults($defaults);
            }
        }
    }

    /**
     * Adds requirements to all listens.
     *
     * An existing requirement under the same name in a listen will be overridden.
     */
    public function addRequirements(array $requirements): void
    {
        if ($requirements) {
            foreach ($this->listens as $listen) {
                $listen->addRequirements($requirements);
            }
        }
    }

    /**
     * Adds options to all listens.
     *
     * An existing option value under the same name in a listen will be overridden.
     */
    public function addOptions(array $options): void
    {
        if ($options) {
            foreach ($this->listens as $listen) {
                $listen->addOptions($options);
            }
        }
    }

    /**
     * Sets the methods (e.g. 'TEXT') all child listens are restricted to.
     *
     * @param string|string[] $methods The method or an array of methods
     */
    public function setMethods(string|array $methods): void
    {
        foreach ($this->listens as $listen) {
            $listen->setMethods($methods);
        }
    }

    /**
     * Sets an alias for an existing listen.
     *
     * @param string $name  The alias to create
     * @param string $alias The listen to alias
     *
     * @throws InvalidArgumentException if the alias is for itself
     */
    public function addAlias(string $name, string $alias): Alias
    {
        if ($name === $alias) {
            throw new InvalidArgumentException(\sprintf('Listen alias "%s" can not reference itself.', $name));
        }

        unset($this->listens[$name], $this->priorities[$name]);

        return $this->aliases[$name] = new Alias($alias);
    }

    /**
     * @return array<string, Alias>
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    public function getAlias(string $name): ?Alias
    {
        return $this->aliases[$name] ?? null;
    }

    public function getPriority(string $name): ?int
    {
        return $this->priorities[$name] ?? null;
    }
}
