<?php

namespace LaraGram\Console\Completion;

use LaraGram\Console\Input\InputOption;

final class CompletionSuggestions
{
    private array $valueSuggestions = [];
    private array $optionSuggestions = [];

    /**
     * Add a suggested value for an input option or argument.
     *
     * @return $this
     */
    public function suggestValue(string|Suggestion $value): static
    {
        $this->valueSuggestions[] = !$value instanceof Suggestion ? new Suggestion($value) : $value;

        return $this;
    }

    /**
     * Add multiple suggested values at once for an input option or argument.
     *
     * @param list<string|Suggestion> $values
     *
     * @return $this
     */
    public function suggestValues(array $values): static
    {
        foreach ($values as $value) {
            $this->suggestValue($value);
        }

        return $this;
    }

    /**
     * Add a suggestion for an input option name.
     *
     * @return $this
     */
    public function suggestOption(InputOption $option): static
    {
        $this->optionSuggestions[] = $option;

        return $this;
    }

    /**
     * Add multiple suggestions for input option names at once.
     *
     * @param InputOption[] $options
     *
     * @return $this
     */
    public function suggestOptions(array $options): static
    {
        foreach ($options as $option) {
            $this->suggestOption($option);
        }

        return $this;
    }

    /**
     * @return InputOption[]
     */
    public function getOptionSuggestions(): array
    {
        return $this->optionSuggestions;
    }

    /**
     * @return Suggestion[]
     */
    public function getValueSuggestions(): array
    {
        return $this->valueSuggestions;
    }
}
