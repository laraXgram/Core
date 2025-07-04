<?php

declare(strict_types=1);

namespace LaraGram\Support\Env\Util;

/**
 * @template T
 * @template E
 *
 * @extends \LaraGram\Support\Env\Util\Result<T,E>
 */
final class Error extends Result
{
    /**
     * @var E
     */
    private $value;

    /**
     * Internal constructor for an error value.
     *
     * @param E $value
     *
     * @return void
     */
    private function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Create a new error value.
     *
     * @template F
     *
     * @param F $value
     *
     * @return \LaraGram\Support\Env\Util\Result<T,F>
     */
    public static function create($value)
    {
        return new self($value);
    }

    /**
     * Get the success option value.
     *
     * @return \LaraGram\Support\Env\Util\Option<T>
     */
    public function success()
    {
        return None::create();
    }

    /**
     * Map over the success value.
     *
     * @template S
     *
     * @param callable(T):S $f
     *
     * @return \LaraGram\Support\Env\Util\Result<S,E>
     */
    public function map(callable $f)
    {
        return self::create($this->value);
    }

    /**
     * Flat map over the success value.
     *
     * @template S
     * @template F
     *
     * @param callable(T):\LaraGram\Support\Env\Util\Result<S,F> $f
     *
     * @return \LaraGram\Support\Env\Util\Result<S,F>
     */
    public function flatMap(callable $f)
    {
        /** @var \LaraGram\Support\Env\Util\Result<S,F> */
        return self::create($this->value);
    }

    /**
     * Get the error option value.
     *
     * @return \LaraGram\Support\Env\Util\Option<E>
     */
    public function error()
    {
        return Some::create($this->value);
    }

    /**
     * Map over the error value.
     *
     * @template F
     *
     * @param callable(E):F $f
     *
     * @return \LaraGram\Support\Env\Util\Result<T,F>
     */
    public function mapError(callable $f)
    {
        return self::create($f($this->value));
    }
}
