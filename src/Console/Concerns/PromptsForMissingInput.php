<?php

namespace LaraGram\Console\Concerns;

use Closure;
use LaraGram\Contracts\Console\PromptsForMissingInput as PromptsForMissingInputContract;
use LaraGram\Support\Arr;
use LaraGram\Support\Collection;
use LaraGram\Console\Input\InputArgument;
use LaraGram\Console\Input\InputInterface;
use LaraGram\Console\Output\OutputInterface;

use function LaraGram\Console\Prompts\text;

trait PromptsForMissingInput
{
    /**
     * Interact with the user before validating the input.
     *
     * @param  \LaraGram\Console\Input\InputInterface  $input
     * @param  \LaraGram\Console\Output\OutputInterface  $output
     * @return void
     */
    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        parent::interact($input, $output);

        if ($this instanceof PromptsForMissingInputContract) {
            $this->promptForMissingArguments($input, $output);
        }
    }

    /**
     * Prompt the user for any missing arguments.
     *
     * @param  \LaraGram\Console\Input\InputInterface  $input
     * @param  \LaraGram\Console\Output\OutputInterface  $output
     * @return void
     */
    protected function promptForMissingArguments(InputInterface $input, OutputInterface $output)
    {
        $prompted = (new Collection($this->getDefinition()->getArguments()))
            ->reject(fn (InputArgument $argument) => $argument->getName() === 'command')
            ->filter(fn (InputArgument $argument) => $argument->isRequired() && match (true) {
                $argument->isArray() => empty($input->getArgument($argument->getName())),
                default => is_null($input->getArgument($argument->getName())),
            })
            ->each(function (InputArgument $argument) use ($input) {
                $label = $this->promptForMissingArgumentsUsing()[$argument->getName()] ??
                    'What is '.lcfirst($argument->getDescription() ?: ('the '.$argument->getName())).'?';

                if ($label instanceof Closure) {
                    return $input->setArgument($argument->getName(), $argument->isArray() ? Arr::wrap($label()) : $label());
                }

                if (is_array($label)) {
                    [$label, $placeholder] = $label;
                }

                $answer = text(
                    label: $label,
                    placeholder: $placeholder ?? '',
                    validate: fn ($value) => empty($value) ? "The {$argument->getName()} is required." : null,
                );

                $input->setArgument($argument->getName(), $argument->isArray() ? [$answer] : $answer);
            })
            ->isNotEmpty();

        if ($prompted) {
            $this->afterPromptingForMissingArguments($input, $output);
        }
    }

    /**
     * Prompt for missing input arguments using the returned questions.
     *
     * @return array<string, string|array{string, string}|\Closure(): (array<int|string>|string|int|bool)>
     */
    protected function promptForMissingArgumentsUsing()
    {
        return [];
    }

    /**
     * Perform actions after the user was prompted for missing arguments.
     *
     * @param  \LaraGram\Console\Input\InputInterface  $input
     * @param  \LaraGram\Console\Output\OutputInterface  $output
     * @return void
     */
    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output)
    {
        //
    }

    /**
     * Whether the input contains any options that differ from the default values.
     *
     * @param  \LaraGram\Console\Input\InputInterface  $input
     * @return bool
     */
    protected function didReceiveOptions(InputInterface $input)
    {
        return (new Collection($this->getDefinition()->getOptions()))
            ->reject(fn ($option) => $input->getOption($option->getName()) === $option->getDefault())
            ->isNotEmpty();
    }
}
