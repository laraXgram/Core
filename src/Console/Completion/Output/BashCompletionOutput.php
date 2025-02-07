<?php

namespace LaraGram\Console\Completion\Output;

use LaraGram\Console\Completion\CompletionSuggestions;
use LaraGram\Console\Output\OutputInterface;

class BashCompletionOutput implements CompletionOutputInterface
{
    public function write(CompletionSuggestions $suggestions, OutputInterface $output): void
    {
        $values = $suggestions->getValueSuggestions();
        foreach ($suggestions->getOptionSuggestions() as $option) {
            $values[] = '--'.$option->getName();
            if ($option->isNegatable()) {
                $values[] = '--no-'.$option->getName();
            }
        }
        $output->writeln(implode("\n", $values));
    }
}
