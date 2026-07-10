<?php

namespace LaraGram\Console\Helper\Cron;

interface FieldFactoryInterface
{
    public function getField(int $position): FieldInterface;
}
