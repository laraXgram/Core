<?php

declare(strict_types=1);

namespace LaraGram\Support\Env\Util;

/**
 * @template T
 * @template E
 *
 * @extends \LaraGram\Support\Env\Util\Result<T,E>
 */
final class Success extends Result
{
    /**
     * @var T
     */
    private $value;

    /**
     * Internal constructor for a success value.
     *
     * @param T $value
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
     * @template S
     *
     * @param S $value
     *
     * @return \LaraGram\Support\Env\Util\Result<S,E>
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
        return Some::create($this->value);
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
        return self::create($f($this->value));
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
        return $f($this->value);
    }

    /**
     * Get the error option value.
     *
     * @return \LaraGram\Support\Env\Util\Option<E>
     */
    public function error()
    {
        return None::create();
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
        return self::create($this->value);
    }
}
