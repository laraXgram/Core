<?php

namespace LaraGram\Support\String\Uid;

class UuidV8 extends Uuid
{
    protected const TYPE = 8;

    public function __construct(string $uuid)
    {
        parent::__construct($uuid, true);
    }
}
