<?php

namespace LaraGram\Http\VarDumper\Exceptions;

class ThrowingCasterException extends \Exception
{
    /**
     * @param \Throwable $prev The exception thrown from the caster
     */
    public function __construct(\Throwable $prev)
    {
        parent::__construct('Unexpected '.$prev::class.' thrown from a caster: '.$prev->getMessage(), 0, $prev);
    }
}
