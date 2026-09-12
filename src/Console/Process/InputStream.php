<?php

namespace LaraGram\Console\Process;

use LaraGram\Console\Process\Exception\RuntimeException;

class InputStream implements \IteratorAggregate
{
    private ?\Closure $onEmpty = null;
    private array $input = [];
    private bool $open = true;

    /**
     * Sets a callback that is called when the write buffer becomes empty.
     */
    public function onEmpty(?callable $onEmpty = null): void
    {
        $this->onEmpty = null !== $onEmpty ? $onEmpty(...) : null;
    }

    /**
     * Appends an input to the write buffer.
     *
     * @param resource|string|int|float|bool|\Traversable|null $input The input to append as scalar,
     *                                                                stream resource or \Traversable
     */
    public function write(mixed $input): void
    {
        if (null === $input) {
            return;
        }
        if ($this->isClosed()) {
            throw new RuntimeException(\sprintf('"%s" is closed.', static::class));
        }
        $this->input[] = ProcessUtils::validateInput(__METHOD__, $input);
    }

    /**
     * Closes the write buffer.
     */
    public function close(): void
    {
        $this->open = false;
    }

    /**
     * Tells whether the write buffer is closed or not.
     */
    public function isClosed(): bool
    {
        return !$this->open;
    }

    public function getIterator(): \Traversable
    {
        $this->open = true;

        while ($this->open || $this->input) {
            if (!$this->input) {
                yield '';
                continue;
            }
            $current = array_shift($this->input);

            if ($current instanceof \Iterator) {
                yield from $current;
            } else {
                yield $current;
            }
            if (!$this->input && $this->open && null !== $onEmpty = $this->onEmpty) {
                $this->write($onEmpty($this));
            }
        }
    }
}
