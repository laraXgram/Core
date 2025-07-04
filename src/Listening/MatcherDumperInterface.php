<?php

namespace LaraGram\Listening;

interface MatcherDumperInterface
{
    /**
     * Dumps a set of listens to a string representation of executable code
     * that can then be used to match a request against these listens.
     */
    public function dump(array $options = []): string;

    /**
     * Gets the listens to dump.
     */
    public function getListens(): BaseListenCollection;
}
