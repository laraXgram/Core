<?php

namespace LaraGram\Support\Env\Util;

use EmptyIterator;

/**
 * @extends Option<mixed>
 */
final class None extends Option
{
    /** @var None|null */
    private static $instance;

    /**
     * @return None
     */
    public static function create(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function get()
    {
        throw new \RuntimeException('None has no value.');
    }

    public function getOrCall($callable)
    {
        return $callable();
    }

    public function getOrElse($default)
    {
        return $default;
    }

    public function getOrThrow(\Exception $ex)
    {
        throw $ex;
    }

    public function isEmpty(): bool
    {
        return true;
    }

    public function isDefined(): bool
    {
        return false;
    }

    public function orElse(Option $else)
    {
        return $else;
    }

    public function ifDefined($callable)
    {
        // Just do nothing in that case.
    }

    public function forAll($callable)
    {
        return $this;
    }

    public function map($callable)
    {
        return $this;
    }

    public function flatMap($callable)
    {
        return $this;
    }

    public function filter($callable)
    {
        return $this;
    }

    public function filterNot($callable)
    {
        return $this;
    }

    public function select($value)
    {
        return $this;
    }

    public function reject($value)
    {
        return $this;
    }

    public function getIterator(): EmptyIterator
    {
        return new EmptyIterator();
    }

    public function foldLeft($initialValue, $callable)
    {
        return $initialValue;
    }

    public function foldRight($initialValue, $callable)
    {
        return $initialValue;
    }

    private function __construct()
    {
    }
}
