<?php

namespace LaraGram\Support\Uri\Contracts;

/**
 * @method self normalize() returns the normalized string representation of the component
 */
interface FragmentInterface extends UriComponentInterface
{
    /**
     * Returns the decoded fragment.
     */
    public function decoded(): ?string;
}
