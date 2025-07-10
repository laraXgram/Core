<?php

namespace LaraGram\Support;

use LaraGram\Tempora\Tempora as BaseTempora;
use LaraGram\Tempora\TemporaImmutable as BaseTemporaImmutable;
use LaraGram\Support\Traits\Conditionable;
use LaraGram\Support\String\Uid\Uuid;
use LaraGram\Support\String\Uid\Ulid;

class Tempora extends BaseTempora
{
    use Conditionable;

    /**
     * {@inheritdoc}
     */
    public static function setTestNow(mixed $testNow = null): void
    {
        BaseTempora::setTestNow($testNow);
        BaseTemporaImmutable::setTestNow($testNow);
    }

    /**
     * Create a Tempora instance from a given ordered UUID or ULID.
     */
    public static function createFromId(Uuid|Ulid|string $id): static
    {
        if (is_string($id)) {
            $id = Ulid::isValid($id) ? Ulid::fromString($id) : Uuid::fromString($id);
        }

        return static::createFromInterface($id->getDateTime());
    }
}
