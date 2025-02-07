<?php

declare(strict_types=1);

namespace LaraGram\Console\Prompts\Convertor\Helpers;

use LaraGRam\Console\Formatter\OutputFormatter;
use LaraGRam\Console\Helper\LaraGramQuestionHelper;
use LaraGRam\Console\Output\OutputInterface;
use LaraGRam\Console\Question\Question;

/**
 * @internal
 */
final class QuestionHelper extends LaraGramQuestionHelper
{
    /**
     * {@inheritdoc}
     */
    protected function writePrompt(OutputInterface $output, Question $question): void
    {
        $text = OutputFormatter::escapeTrailingBackslash($question->getQuestion());
        $output->write($text);
    }
}
