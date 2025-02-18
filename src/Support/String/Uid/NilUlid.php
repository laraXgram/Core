<?php

namespace LaraGram\Support\String\Uid;

class NilUlid extends Ulid
{
    public function __construct()
    {
        $this->uid = parent::NIL;
    }
}
