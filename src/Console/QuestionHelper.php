<?php

namespace LaraGram\Console;

use LaraGram\Console\View\Components\TwoColumnDetail;
use LaraGram\Console\Formatter\OutputFormatter;
use LaraGram\Console\Helper\LaraGramQuestionHelper;
use LaraGram\Console\Output\OutputInterface;
use LaraGram\Console\Question\ChoiceQuestion;
use LaraGram\Console\Question\ConfirmationQuestion;
use LaraGram\Console\Question\Question;

class QuestionHelper extends LaraGramQuestionHelper
{
    /**
     * {@inheritdoc}
     *
     * @return void
     */
    #[\Override]
    protected function writePrompt(OutputInterface $output, Question $question): void
    {
        $text = OutputFormatter::escapeTrailingBackslash($question->getQuestion());

        $text = $this->ensureEndsWithPunctuation($text);

        $text = "  <fg=default;options=bold>$text</></>";

        $default = $question->getDefault();

        if ($question->isMultiline()) {
            $text .= sprintf(' (press %s to continue)', 'Windows' == PHP_OS_FAMILY
                ? '<comment>Ctrl+Z</comment> then <comment>Enter</comment>'
                : '<comment>Ctrl+D</comment>');
        }

        switch (true) {
            case null === $default:
                $text = sprintf('<info>%s</info>', $text);

                break;

            case $question instanceof ConfirmationQuestion:
                $text = sprintf('<info>%s (yes/no)</info> [<comment>%s</comment>]', $text, $default ? 'yes' : 'no');

                break;

            case $question instanceof ChoiceQuestion:
                $choices = $question->getChoices();
                $text = sprintf('<info>%s</info> [<comment>%s</comment>]', $text, OutputFormatter::escape($choices[$default] ?? $default));

                break;

            default:
                $text = sprintf('<info>%s</info> [<comment>%s</comment>]', $text, OutputFormatter::escape($default));

                break;
        }

        $output->writeln($text);

        if ($question instanceof ChoiceQuestion) {
            foreach ($question->getChoices() as $key => $value) {
                $twoColumnDetail = new TwoColumnDetail($output);
                $twoColumnDetail->render($value, $key);
            }
        }

        $output->write('<options=bold>❯ </>');
    }

    /**
     * Ensures the given string ends with punctuation.
     *
     * @param  string  $string
     * @return string
     */
    protected function ensureEndsWithPunctuation($string)
    {
        if (!in_array(substr($string, -1), ['?', ':', '!', '.'])) {
            return "$string:";
        }

        return $string;
    }
}
