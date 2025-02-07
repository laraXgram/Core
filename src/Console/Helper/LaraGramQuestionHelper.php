<?php

namespace LaraGram\Console\Helper;

use LaraGram\Console\Formatter\OutputFormatter;
use LaraGram\Console\Output\OutputInterface;
use LaraGram\Console\Question\ChoiceQuestion;
use LaraGram\Console\Question\ConfirmationQuestion;
use LaraGram\Console\Question\Question;
use LaraGram\Console\Style\Style;

class LaraGramQuestionHelper extends QuestionHelper
{
    protected function writePrompt(OutputInterface $output, Question $question): void
    {
        $text = OutputFormatter::escapeTrailingBackslash($question->getQuestion());
        $default = $question->getDefault();

        if ($question->isMultiline()) {
            $text .= \sprintf(' (press %s to continue)', $this->getEofShortcut());
        }

        switch (true) {
            case null === $default:
                $text = \sprintf(' <info>%s</info>:', $text);

                break;

            case $question instanceof ConfirmationQuestion:
                $text = \sprintf(' <info>%s (yes/no)</info> [<comment>%s</comment>]:', $text, $default ? 'yes' : 'no');

                break;

            case $question instanceof ChoiceQuestion && $question->isMultiselect():
                $choices = $question->getChoices();
                $default = explode(',', $default);

                foreach ($default as $key => $value) {
                    $default[$key] = $choices[trim($value)];
                }

                $text = \sprintf(' <info>%s</info> [<comment>%s</comment>]:', $text, OutputFormatter::escape(implode(', ', $default)));

                break;

            case $question instanceof ChoiceQuestion:
                $choices = $question->getChoices();
                $text = \sprintf(' <info>%s</info> [<comment>%s</comment>]:', $text, OutputFormatter::escape($choices[$default] ?? $default));

                break;

            default:
                $text = \sprintf(' <info>%s</info> [<comment>%s</comment>]:', $text, OutputFormatter::escape($default));
        }

        $output->writeln($text);

        $prompt = ' > ';

        if ($question instanceof ChoiceQuestion) {
            $output->writeln($this->formatChoiceQuestionChoices($question, 'comment'));

            $prompt = $question->getPrompt();
        }

        $output->write($prompt);
    }

    protected function writeError(OutputInterface $output, \Exception $error): void
    {
        if ($output instanceof Style) {
            $output->newLine();
            $output->error($error->getMessage());

            return;
        }

        parent::writeError($output, $error);
    }

    private function getEofShortcut(): string
    {
        if ('Windows' === \PHP_OS_FAMILY) {
            return '<comment>Ctrl+Z</comment> then <comment>Enter</comment>';
        }

        return '<comment>Ctrl+D</comment>';
    }
}
