<?php

declare(strict_types=1);

namespace LaraGram\Support\Env\Util;

/**
 * @template T
 * @template E
 */
abstract class Result
{
    /**
     * Get the success option value.
     *
     * @return \LaraGram\Support\Env\Util\Option<T>
     */
    abstract public function success();

    /**
     * Map over the success value.
     *
     * @template S
     *
     * @param callable(T):S $f
     *
     * @return \LaraGram\Support\Env\Util\Result<S,E>
     */
    abstract public function map(callable $f);

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
    abstract public function flatMap(callable $f);

    /**
     * Get the error option value.
     *
     * @return \LaraGram\Support\Env\Util\Option<E>
     */
    abstract public function error();

    /**
     * Map over the error value.
     *
     * @template F
     *
     * @param callable(E):F $f
     *
     * @return \LaraGram\Support\Env\Util\Result<T,F>
     */
    abstract public function mapError(callable $f);
}
