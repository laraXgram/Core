<?php

declare(strict_types=1);

namespace LaraGram\Console\Prompts\Convertor;

use ReflectionClass;
use LaraGram\Console\Helper\LaraGramQuestionHelper;
use LaraGram\Console\Input\ArgvInput;
use LaraGram\Console\Input\StreamableInputInterface;
use LaraGram\Console\Question\Question as LaraGramQuestion;
use LaraGram\Console\Prompts\Convertor\Helpers\QuestionHelper;

/**
 * @internal
 */
final class Question
{
    /**
     * The streamable input to receive the input from the user.
     */
    private static ?StreamableInputInterface $streamableInput;

    /**
     * An instance of LaraGram's question helper.
     */
    private LaraGramQuestionHelper $helper;

    public function __construct(?LaraGramQuestionHelper $helper = null)
    {
        $this->helper = $helper ?? new QuestionHelper;
    }

    /**
     * Sets the streamable input implementation.
     */
    public static function setStreamableInput(?StreamableInputInterface $streamableInput): void
    {
        self::$streamableInput = $streamableInput ?? new ArgvInput;
    }

    /**
     * Gets the streamable input implementation.
     */
    public static function getStreamableInput(): StreamableInputInterface
    {
        return self::$streamableInput ??= new ArgvInput;
    }

    /**
     * Renders a prompt to the user.
     *
     * @param  iterable<array-key, string>|null  $autocomplete
     */
    public function ask(string $question, ?iterable $autocomplete = null): mixed
    {
        $html = (new HtmlRenderer)->parse($question)->toString();

        $question = new LaraGramQuestion($html);

        if ($autocomplete !== null) {
            $question->setAutocompleterValues($autocomplete);
        }

        $output = Convertor::getRenderer();

        if ($output instanceof LaraGramStyle) {
            $property = (new ReflectionClass(LaraGramStyle::class))
                ->getProperty('questionHelper');

            $property->setAccessible(true);

            $currentHelper = $property->isInitialized($output)
                ? $property->getValue($output)
                : new LaraGramQuestionHelper;

            $property->setValue($output, new QuestionHelper);

            try {
                return $output->askQuestion($question);
            } finally {
                $property->setValue($output, $currentHelper);
            }
        }

        return $this->helper->ask(
            self::getStreamableInput(),
            Convertor::getRenderer(),
            $question,
        );
    }
}
