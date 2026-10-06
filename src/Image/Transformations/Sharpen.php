<?php

namespace LaraGram\Image\Transformations;

use LaraGram\Contracts\Image\Transformation;

class Sharpen implements Transformation
{
    /**
     * @param  positive-int  $amount
     */
    public function __construct(public readonly int $amount)
    {
        //
    }
}
