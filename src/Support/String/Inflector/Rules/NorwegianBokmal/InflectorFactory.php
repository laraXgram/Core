<?php

declare(strict_types=1);

namespace LaraGram\Support\String\Inflector\Rules\NorwegianBokmal;

use LaraGram\Support\String\Inflector\GenericLanguageInflectorFactory;
use LaraGram\Support\String\Inflector\Rules\Ruleset;

final class InflectorFactory extends GenericLanguageInflectorFactory
{
    protected function getSingularRuleset(): Ruleset
    {
        return Rules::getSingularRuleset();
    }

    protected function getPluralRuleset(): Ruleset
    {
        return Rules::getPluralRuleset();
    }
}
