<?php

namespace LaraGram\Support\Uri\Contracts;

interface Conditionable
{
    /**
     * Apply the callback if the given "condition" is (or resolves to) true.
     *
     * @param (callable(static): bool)|bool $condition
     * @param callable(static): (static|null) $onSuccess
     * @param ?callable(static): (static|null) $onFail
     */
    public function when(callable|bool $condition, callable $onSuccess, ?callable $onFail = null): static;
}
