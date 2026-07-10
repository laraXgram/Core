<?php

namespace LaraGram\Support\Uri;

use InvalidArgumentException;
use LaraGram\Support\Uri\Contracts\UriException;

class TemplateCanNotBeExpanded extends InvalidArgumentException implements UriException
{
    public readonly array $variablesNames;

    public function __construct(string $message = '', string ...$variableNames)
    {
        parent::__construct($message, 0, null);

        $this->variablesNames = $variableNames;
    }

    public static function dueToUnableToProcessValueListWithPrefix(string $variableName): self
    {
        return new self('The ":" modifier cannot be applied on "'.$variableName.'" since it is a list of values.', $variableName);
    }

    public static function dueToNestedListOfValue(string $variableName): self
    {
        return new self('The "'.$variableName.'" cannot be a nested list.', $variableName);
    }

    public static function dueToMissingVariables(string ...$variableNames): self
    {
        return new self('The following required variables are missing: `'.implode('`, `', $variableNames).'`.', ...$variableNames);
    }
}
