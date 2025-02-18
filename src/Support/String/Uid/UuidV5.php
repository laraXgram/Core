<?php

namespace LaraGram\Support\String\Uid;

class UuidV5 extends Uuid
{
    protected const TYPE = 5;

    public function __construct(string $uuid)
    {
        parent::__construct($uuid, true);
    }
}
