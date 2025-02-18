<?php

namespace LaraGram\Support\String\Uid;

interface TimeBasedUidInterface
{
    public function getDateTime(): \DateTimeImmutable;
}
