<?php

declare(strict_types=1);

namespace LaraGram\Support\String\Inflector\Rules\Turkish;

use LaraGram\Support\String\Inflector\Rules\Patterns;
use LaraGram\Support\String\Inflector\Rules\Ruleset;
use LaraGram\Support\String\Inflector\Rules\Substitutions;
use LaraGram\Support\String\Inflector\Rules\Transformations;

final class Rules
{
    public static function getSingularRuleset(): Ruleset
    {
        return new Ruleset(
            new Transformations(...Inflectible::getSingular()),
            new Patterns(...Uninflected::getSingular()),
            (new Substitutions(...Inflectible::getIrregular()))->getFlippedSubstitutions()
        );
    }

    public static function getPluralRuleset(): Ruleset
    {
        return new Ruleset(
            new Transformations(...Inflectible::getPlural()),
            new Patterns(...Uninflected::getPlural()),
            new Substitutions(...Inflectible::getIrregular())
        );
    }
}
