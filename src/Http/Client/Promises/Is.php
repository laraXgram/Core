<?php

declare(strict_types=1);

namespace LaraGram\Http\Client\Promises;

/**
 * @deprecated PHP 8.6 deprecates "is" as a class name. Use State instead.
 */
final class Is
{
    private function __construct()
    {
    }

    /**
     * Returns true if a promise is pending.
     *
     * @deprecated Use State::isPending() instead.
     */
    public static function pending(PromiseInterface $promise): bool
    {
        return State::isPending($promise);
    }

    /**
     * Returns true if a promise is fulfilled or rejected.
     *
     * @deprecated Use State::isSettled() instead.
     */
    public static function settled(PromiseInterface $promise): bool
    {
        return State::isSettled($promise);
    }

    /**
     * Returns true if a promise is fulfilled.
     *
     * @deprecated Use State::isFulfilled() instead.
     */
    public static function fulfilled(PromiseInterface $promise): bool
    {
        return State::isFulfilled($promise);
    }

    /**
     * Returns true if a promise is rejected.
     *
     * @deprecated Use State::isRejected() instead.
     */
    public static function rejected(PromiseInterface $promise): bool
    {
        return State::isRejected($promise);
    }
}
