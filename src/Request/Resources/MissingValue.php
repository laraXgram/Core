<?php

namespace LaraGram\Request\Resources;

class MissingValue implements PotentiallyMissing
{
    /**
     * Determine if the object should be considered "missing".
     *
     * @return bool
     */
    public function isMissing()
    {
        return true;
    }
}
