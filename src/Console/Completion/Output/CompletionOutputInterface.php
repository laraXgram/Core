<?php

namespace LaraGram\Console\Completion\Output;

use LaraGram\Console\Completion\CompletionSuggestions;
use LaraGram\Console\Output\OutputInterface;

interface CompletionOutputInterface
{
    public function write(CompletionSuggestions $suggestions, OutputInterface $output): void;
}
