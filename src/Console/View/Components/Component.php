<?php

namespace LaraGram\Console\View\Components;

use LaraGram\Console\OutputStyle;
use LaraGram\Console\QuestionHelper;
use ReflectionClass;
use LaraGram\Console\Helper\LaraGramQuestionHelper;

use function LaraGram\Console\Prompts\Convertor\render;
use function LaraGram\Console\Prompts\Convertor\renderUsing;

abstract class Component
{
    /**
     * The output style implementation.
     *
     * @var \LaraGram\Console\OutputStyle
     */
    protected $output;

    /**
     * The list of mutators to apply on the view data.
     *
     * @var array<int, callable(string): string>
     */
    protected $mutators;

    /**
     * Creates a new component instance.
     *
     * @param  \LaraGram\Console\OutputStyle  $output
     * @return void
     */
    public function __construct($output)
    {
        $this->output = $output;
    }

    /**
     * Renders the given view.
     *
     * @param  string  $view
     * @param  \LaraGram\Contracts\Support\Arrayable|array  $data
     * @param  int  $verbosity
     * @return void
     */
    protected function renderView($view, $data, $verbosity)
    {
        renderUsing($this->output);

        render((string) $this->compile($view, $data), $verbosity);
    }

    /**
     * Compile the given view contents.
     *
     * @param  string  $view
     * @param  array  $data
     * @return void
     */
    protected function compile($view, $data)
    {
        extract($data);

        ob_start();

        include __DIR__."/../../resources/views/components/$view.php";

        $contents = ob_get_contents();
        ob_end_clean();

        return $contents;
    }

    /**
     * Mutates the given data with the given set of mutators.
     *
     * @param  array<int, string>|string  $data
     * @param  array<int, callable(string): string>  $mutators
     * @return array<int, string>|string
     */
    protected function mutate($data, $mutators)
    {
        foreach ($mutators as $mutator) {
            $mutator = new $mutator;

            if (is_iterable($data)) {
                foreach ($data as $key => $value) {
                    $data[$key] = $mutator($value);
                }
            } else {
                $data = $mutator($data);
            }
        }

        return $data;
    }

    /**
     * Eventually performs a question using the component's question helper.
     *
     * @param  callable  $callable
     * @return mixed
     */
    protected function usingQuestionHelper($callable)
    {
        $reflectionClass = new ReflectionClass(OutputStyle::class);
        $parentClass = $reflectionClass->getParentClass();
        $property = $parentClass->getProperty('questionHelper');

        $currentHelper = $property->isInitialized($this->output)
            ? $property->getValue($this->output)
            : new LaraGramQuestionHelper();

        $property->setValue($this->output, new QuestionHelper);

        try {
            return $callable();
        } finally {
            $property->setValue($this->output, $currentHelper);
        }
    }
}
