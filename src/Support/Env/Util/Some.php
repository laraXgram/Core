<?php

namespace LaraGram\Support\Env\Util;

use ArrayIterator;

/**
 * @template T
 *
 * @extends Option<T>
 */
final class Some extends Option
{
    /** @var T */
    private $value;

    /**
     * @param T $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @template U
     *
     * @param U $value
     *
     * @return Some<U>
     */
    public static function create($value): self
    {
        return new self($value);
    }

    public function isDefined(): bool
    {
        return true;
    }

    public function isEmpty(): bool
    {
        return false;
    }

    public function get()
    {
        return $this->value;
    }

    public function getOrElse($default)
    {
        return $this->value;
    }

    public function getOrCall($callable)
    {
        return $this->value;
    }

    public function getOrThrow(\Exception $ex)
    {
        return $this->value;
    }

    public function orElse(Option $else)
    {
        return $this;
    }

    public function ifDefined($callable)
    {
        $this->forAll($callable);
    }

    public function forAll($callable)
    {
        $callable($this->value);

        return $this;
    }

    public function map($callable)
    {
        return new self($callable($this->value));
    }

    public function flatMap($callable)
    {
        /** @var mixed */
        $rs = $callable($this->value);
        if (!$rs instanceof Option) {
            throw new \RuntimeException('Callables passed to flatMap() must return an Option. Maybe you should use map() instead?');
        }

        return $rs;
    }

    public function filter($callable)
    {
        if (true === $callable($this->value)) {
            return $this;
        }

        return None::create();
    }

    public function filterNot($callable)
    {
        if (false === $callable($this->value)) {
            return $this;
        }

        return None::create();
    }

    public function select($value)
    {
        if ($this->value === $value) {
            return $this;
        }

        return None::create();
    }

    public function reject($value)
    {
        if ($this->value === $value) {
            return None::create();
        }

        return $this;
    }

    /**
     * @return ArrayIterator<int, T>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator([$this->value]);
    }

    public function foldLeft($initialValue, $callable)
    {
        return $callable($initialValue, $this->value);
    }

    public function foldRight($initialValue, $callable)
    {
        return $callable($this->value, $initialValue);
    }
}
