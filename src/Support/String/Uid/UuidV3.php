<?php

namespace LaraGram\Support\String\Uid;

class UuidV3 extends Uuid
{
    protected const TYPE = 3;

    public function __construct(string $uuid)
    {
        parent::__construct($uuid, true);
    }
}
