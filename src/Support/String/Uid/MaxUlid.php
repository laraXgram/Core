<?php

namespace LaraGram\Support\String\Uid;

class MaxUlid extends Ulid
{
    public function __construct()
    {
        $this->uid = parent::MAX;
    }
}
