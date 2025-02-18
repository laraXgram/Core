<?php

namespace LaraGram\Support\String\Uid;

/**
 * @internal
 */
interface HashableInterface
{
    public function equals(mixed $other): bool;

    public function hash(): string;
}