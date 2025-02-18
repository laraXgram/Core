<?php

namespace LaraGram\Support\String\Uid;

class MaxUuid extends Uuid
{
    protected const TYPE = -1;

    public function __construct()
    {
        $this->uid = parent::MAX;
    }
}
