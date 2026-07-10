<?php

namespace LaraGram\Support\Uri\Contracts;

use Stringable;

/**
 * @see https://wicg.github.io/scroll-to-text-fragment/#the-fragment-directive
 *
 * @method string toFragmentValue() returns the encoded string representation of the directive as a fragment string
 */
interface FragmentDirective extends Stringable
{
    /**
     * The decoded Directive name.
     *
     * @return non-empty-string
     */
    public function name(): string;

    /**
     * The decoded Directive value.
     */
    public function value(): ?string;

    /**
     * The encoded string representation of the directive.
     */
    public function toString(): string;

    /**
     * The encoded string representation of the fragment using
     * the Stringable interface.
     *
     * @see FragmentDirective::toString()
     */
    public function __toString(): string;

    /**
     * Tells whether the submitted value is equals to the string
     * representation of the given directive.
     */
    public function equals(mixed $directive): bool;
}
