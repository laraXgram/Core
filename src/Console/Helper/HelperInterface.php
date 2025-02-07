<?php

namespace LaraGram\Console\Helper;

interface HelperInterface
{
    /**
     * Sets the helper set associated with this helper.
     */
    public function setHelperSet(?HelperSet $helperSet): void;

    /**
     * Gets the helper set associated with this helper.
     */
    public function getHelperSet(): ?HelperSet;

    /**
     * Returns the canonical name of this helper.
     */
    public function getName(): string;
}
