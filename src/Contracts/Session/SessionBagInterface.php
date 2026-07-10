<?php

namespace LaraGram\Contracts\Session;

interface SessionBagInterface
{
    /**
     * Gets this bag's name.
     */
    public function getName(): string;

    /**
     * Initializes the Bag.
     */
    public function initialize(array &$array): void;

    /**
     * Gets the storage key for this bag.
     */
    public function getStorageKey(): string;

    /**
     * Clears out data from bag.
     *
     * @return mixed Whatever data was contained
     */
    public function clear(): mixed;
}
