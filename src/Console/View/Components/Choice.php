<?php

namespace LaraGram\Console\View\Components;

use LaraGram\Console\Question\ChoiceQuestion;

class Choice extends Component
{
    /**
     * Renders the component using the given arguments.
     *
     * @param  string  $question
     * @param  array<array-key, string>  $choices
     * @param  mixed  $default
     * @param  int  $attempts
     * @param  bool  $multiple
     * @return mixed
     */
    public function render($question, $choices, $default = null, $attempts = null, $multiple = false)
    {
        return $this->usingQuestionHelper(
            fn () => $this->output->askQuestion(
                $this->getChoiceQuestion($question, $choices, $default)
                    ->setMaxAttempts($attempts)
                    ->setMultiselect($multiple)
            ),
        );
    }

    /**
     * Get a ChoiceQuestion instance that handles array keys like Prompts.
     *
     * @param  string  $question
     * @param  array  $choices
     * @param  mixed  $default
     * @return \LaraGram\Console\Question\ChoiceQuestion
     */
    protected function getChoiceQuestion($question, $choices, $default)
    {
        return new class($question, $choices, $default) extends ChoiceQuestion
        {
            protected function isAssoc(array $array): bool
            {
                return ! array_is_list($array);
            }
        };
    }
}
