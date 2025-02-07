<?php

namespace LaraGram\Support\String;

enum TruncateMode
{
    /**
     * Will cut exactly at given length.
     *
     * Length: 14
     * Source: Lorem ipsum dolor sit amet
     * Output: Lorem ipsum do
     */
    case Char;

    /**
     * Returns the string up to the last complete word containing the specified length.
     *
     * Length: 14
     * Source: Lorem ipsum dolor sit amet
     * Output: Lorem ipsum
     */
    case WordBefore;

    /**
     * Returns the string up to the complete word after or at the given length.
     *
     * Length: 14
     * Source: Lorem ipsum dolor sit amet
     * Output: Lorem ipsum dolor
     */
    case WordAfter;
}
