<?php

namespace LaraGram\Console\Command;

use LaraGram\Console\Attribute\AsCommand;
use LaraGram\Console\Completion\CompletionInput;
use LaraGram\Console\Completion\CompletionSuggestions;
use LaraGram\Console\Completion\Output\BashCompletionOutput;
use LaraGram\Console\Completion\Output\CompletionOutputInterface;
use LaraGram\Console\Completion\Output\FishCompletionOutput;
use LaraGram\Console\Completion\Output\ZshCompletionOutput;
use LaraGram\Console\Exception\CommandNotFoundException;
use LaraGram\Console\Exception\ExceptionInterface;
use LaraGram\Console\Input\InputInterface;
use LaraGram\Console\Input\InputOption;
use LaraGram\Console\Output\OutputInterface;

#[AsCommand(name: '|_complete', description: 'Internal command to provide shell completion suggestions')]
final class CompleteCommand extends Command
{
    public const COMPLETION_API_VERSION = '1';

    private array $completionOutputs;
    private bool $isDebug = false;

    /**
     * @param array<string, class-string<CompletionOutputInterface>> $completionOutputs A list of additional completion outputs, with shell name as key and FQCN as value
     */
    public function __construct(array $completionOutputs = [])
    {
        // must be set before the parent constructor, as the property value is used in configure()
        $this->completionOutputs = $completionOutputs + [
            'bash' => BashCompletionOutput::class,
            'fish' => FishCompletionOutput::class,
            'zsh' => ZshCompletionOutput::class,
        ];

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('shell', 's', InputOption::VALUE_REQUIRED, 'The shell type ("'.implode('", "', array_keys($this->completionOutputs)).'")')
            ->addOption('input', 'i', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'An array of input tokens (e.g. COMP_WORDS or argv)')
            ->addOption('current', 'c', InputOption::VALUE_REQUIRED, 'The index of the "input" array that the cursor is in (e.g. COMP_CWORD)')
            ->addOption('api-version', 'a', InputOption::VALUE_REQUIRED, 'The API version of the completion script')
            ->addOption('laragram', 'S', InputOption::VALUE_REQUIRED, 'deprecated')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->isDebug = filter_var(getenv('LARAGRAM_COMPLETION_DEBUG'), \FILTER_VALIDATE_BOOL);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $version = $input->getOption('laragram') ? '1' : $input->getOption('api-version');
            if ($version && version_compare($version, self::COMPLETION_API_VERSION, '<')) {
                $message = \sprintf('Completion script version is not supported ("%s" given, ">=%s" required).', $version, self::COMPLETION_API_VERSION);
                $this->log($message);

                $output->writeln($message.' Install the LaraGram completion script again by using the "completion" command.');

                return 126;
            }

            $shell = $input->getOption('shell');
            if (!$shell) {
                throw new \RuntimeException('The "--shell" option must be set.');
            }

            if (!$completionOutput = $this->completionOutputs[$shell] ?? false) {
                throw new \RuntimeException(\sprintf('Shell completion is not supported for your shell: "%s" (supported: "%s").', $shell, implode('", "', array_keys($this->completionOutputs))));
            }

            $completionInput = $this->createCompletionInput($input);
            $suggestions = new CompletionSuggestions();

            $this->log([
                '',
                '<comment>'.date('Y-m-d H:i:s').'</>',
                '<info>Input:</> <comment>("|" indicates the cursor position)</>',
                '  '.$completionInput,
                '<info>Command:</>',
                '  '.implode(' ', $_SERVER['argv']),
                '<info>Messages:</>',
            ]);

            $command = $this->findCommand($completionInput);
            if (null === $command) {
                $this->log('  No command found, completing using the Application class.');

                $this->getApplication()->complete($completionInput, $suggestions);
            } elseif (
                $completionInput->mustSuggestArgumentValuesFor('command')
                && $command->getName() !== $completionInput->getCompletionValue()
                && !\in_array($completionInput->getCompletionValue(), $command->getAliases(), true)
            ) {
                $this->log('  No command found, completing using the Application class.');

                // expand shortcut names ("cache:cl<TAB>") into their full name ("cache:clear")
                $suggestions->suggestValues(array_filter(array_merge([$command->getName()], $command->getAliases())));
            } else {
                $command->mergeApplicationDefinition();
                $completionInput->bind($command->getDefinition());

                if (CompletionInput::TYPE_OPTION_NAME === $completionInput->getCompletionType()) {
                    $this->log('  Completing option names for the <comment>'.($command instanceof LazyCommand ? $command->getCommand() : $command)::class.'</> command.');

                    $suggestions->suggestOptions($command->getDefinition()->getOptions());
                } else {
                    $this->log([
                        '  Completing using the <comment>'.($command instanceof LazyCommand ? $command->getCommand() : $command)::class.'</> class.',
                        '  Completing <comment>'.$completionInput->getCompletionType().'</> for <comment>'.$completionInput->getCompletionName().'</>',
                    ]);
                    if (null !== $compval = $completionInput->getCompletionValue()) {
                        $this->log('  Current value: <comment>'.$compval.'</>');
                    }

                    $command->complete($completionInput, $suggestions);
                }
            }

            /** @var CompletionOutputInterface $completionOutput */
            $completionOutput = new $completionOutput();

            $this->log('<info>Suggestions:</>');
            if ($options = $suggestions->getOptionSuggestions()) {
                $this->log('  --'.implode(' --', array_map(fn ($o) => $o->getName(), $options)));
            } elseif ($values = $suggestions->getValueSuggestions()) {
                $this->log('  '.implode(' ', $values));
            } else {
                $this->log('  <comment>No suggestions were provided</>');
            }

            $completionOutput->write($suggestions, $output);
        } catch (\Throwable $e) {
            $this->log([
                '<error>Error!</error>',
                (string) $e,
            ]);

            if ($output->isDebug()) {
                throw $e;
            }

            return 2;
        }

        return 0;
    }

    private function createCompletionInput(InputInterface $input): CompletionInput
    {
        $currentIndex = $input->getOption('current');
        if (!$currentIndex || !ctype_digit($currentIndex)) {
            throw new \RuntimeException('The "--current" option must be set and it must be an integer.');
        }

        $completionInput = CompletionInput::fromTokens($input->getOption('input'), (int) $currentIndex);

        try {
            $completionInput->bind($this->getApplication()->getDefinition());
        } catch (ExceptionInterface) {
        }

        return $completionInput;
    }

    private function findCommand(CompletionInput $completionInput): ?Command
    {
        try {
            $inputName = $completionInput->getFirstArgument();
            if (null === $inputName) {
                return null;
            }

            return $this->getApplication()->find($inputName);
        } catch (CommandNotFoundException) {
        }

        return null;
    }

    private function log($messages): void
    {
        if (!$this->isDebug) {
            return;
        }

        $commandName = basename($_SERVER['argv'][0]);
        file_put_contents(sys_get_temp_dir().'/sf_'.$commandName.'.log', implode(\PHP_EOL, (array) $messages).\PHP_EOL, \FILE_APPEND);
    }
}
