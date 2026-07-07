<?php

declare(strict_types=1);

namespace LaraGram\Http\Client\Promises;

/**
 * Interface used with classes that return a promise.
 */
interface PromisorInterface
{
    /**
     * Returns a promise.
     */
    public function promise(): PromiseInterface;
}
