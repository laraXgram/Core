<?php

namespace LaraGram\Filesystem\Aws;

use ArrayAccess;

class Command implements ArrayAccess
{
    /**
     * Create a new command instance.
     *
     * @param  string  $name
     * @param  array<string, mixed>  $args
     */
    public function __construct(protected string $name, protected array $args = [])
    {
        //
    }

    /**
     * Get the name of the operation.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the operation's arguments.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->args;
    }

    /**
     * Determine if the command has the given argument.
     *
     * @param  string  $name
     * @return bool
     */
    public function hasParam(string $name): bool
    {
        return array_key_exists($name, $this->args);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->args[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->args[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->args[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->args[$offset]);
    }
}
