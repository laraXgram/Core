<?php

namespace LaraGram\Conversation;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

final class AnswersBag implements ArrayAccess, Countable, IteratorAggregate
{
    /**
     * @param  array<int|string, \LaraGram\Conversation\Answer>  $answers
     */
    public function __construct(private array $answers = [])
    {
    }

    /**
     * Get an answer by key.
     *
     * @param  int|string  $key
     * @return \LaraGram\Conversation\Answer|null
     */
    public function get(int|string $key, ?Answer $default = null): ?Answer
    {
        return $this->answers[$key] ?? $default;
    }

    /**
     * Determine if an answer exists for the given key.
     *
     * @param  int|string  $key
     * @return bool
     */
    public function has(int|string $key): bool
    {
        return array_key_exists($key, $this->answers);
    }

    /**
     * Get all answers keyed by their key.
     *
     * @return array<int|string, \LaraGram\Conversation\Answer>
     */
    public function all(): array
    {
        return $this->answers;
    }

    /**
     * Get the first answer (handy for single-question conversations).
     *
     * @return \LaraGram\Conversation\Answer|null
     */
    public function first(): ?Answer
    {
        foreach ($this->answers as $answer) {
            return $answer;
        }

        return null;
    }

    /**
     * Get the answer keys.
     *
     * @return array<int, int|string>
     */
    public function keys(): array
    {
        return array_keys($this->answers);
    }

    /**
     * Get the answers as a list.
     *
     * @return array<int, \LaraGram\Conversation\Answer>
     */
    public function values(): array
    {
        return array_values($this->answers);
    }

    /**
     * Reduce the bag to a plain key => natural-value array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(): array
    {
        return array_map(fn (Answer $answer) => $answer->value(), $this->answers);
    }

    /**
     * Count the answers.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->answers);
    }

    /**
     * @return \Traversable<int|string, \LaraGram\Conversation\Answer>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->answers);
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    public function offsetGet(mixed $offset): ?Answer
    {
        return $this->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->answers[] = $value;
        } else {
            $this->answers[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->answers[$offset]);
    }
}
