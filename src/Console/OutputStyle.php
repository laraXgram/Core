<?php

namespace LaraGram\Console;

use LaraGram\Console\Contracts\NewLineAware;
use LaraGram\Console\Input\InputInterface;
use LaraGram\Console\Output\OutputInterface;
use LaraGram\Console\Question\Question;
use LaraGram\Console\Style\Style;

class OutputStyle extends Style implements NewLineAware
{
    /**
     * The output instance.
     *
     * @var \LaraGram\Console\Output\OutputInterface
     */
    private $output;

    /**
     * The number of trailing new lines written by the last output.
     *
     * This is initialized as 1 to account for the new line written by the shell after executing a command.
     *
     * @var int
     */
    protected $newLinesWritten = 1;

    /**
     * If the last output written wrote a new line.
     *
     * @var bool
     *
     * @deprecated use $newLinesWritten
     */
    protected $newLineWritten = false;

    /**
     * Create a new Console OutputStyle instance.
     *
     * @param  \LaraGram\Console\Input\InputInterface  $input
     * @param  \LaraGram\Console\Output\OutputInterface  $output
     * @return void
     */
    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        parent::__construct($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function askQuestion(Question $question): mixed
    {
        try {
            return parent::askQuestion($question);
        } finally {
            $this->newLinesWritten++;
        }
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function write(string|iterable $messages, bool $newline = false, int $options = 0): void
    {
        $this->newLinesWritten = $this->trailingNewLineCount($messages) + (int) $newline;
        $this->newLineWritten = $this->newLinesWritten > 0;

        parent::write($messages, $newline, $options);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function writeln(string|iterable $messages, int $type = self::OUTPUT_NORMAL): void
    {
        if ($this->output->getVerbosity() >= $type) {
            $this->newLinesWritten = $this->trailingNewLineCount($messages) + 1;
            $this->newLineWritten = true;
        }

        parent::writeln($messages, $type);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function newLine(int $count = 1): void
    {
        $this->newLinesWritten += $count;
        $this->newLineWritten = $this->newLinesWritten > 0;

        parent::newLine($count);
    }

    /**
     * {@inheritdoc}
     */
    public function newLinesWritten()
    {
        if ($this->output instanceof static) {
            return $this->output->newLinesWritten();
        }

        return $this->newLinesWritten;
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated use newLinesWritten
     */
    public function newLineWritten()
    {
        if ($this->output instanceof static && $this->output->newLineWritten()) {
            return true;
        }

        return $this->newLineWritten;
    }

    /*
     * Count the number of trailing new lines in a string.
     *
     * @param  string|iterable  $messages
     * @return int
     */
    protected function trailingNewLineCount($messages)
    {
        if (is_iterable($messages)) {
            $string = '';

            foreach ($messages as $message) {
                $string .= $message.PHP_EOL;
            }
        } else {
            $string = $messages;
        }

        return strlen($string) - strlen(rtrim($string, PHP_EOL));
    }

    /**
     * Returns whether verbosity is quiet (-q).
     *
     * @return bool
     */
    public function isQuiet(): bool
    {
        return $this->output->isQuiet();
    }

    /**
     * Returns whether verbosity is verbose (-v).
     *
     * @return bool
     */
    public function isVerbose(): bool
    {
        return $this->output->isVerbose();
    }

    /**
     * Returns whether verbosity is very verbose (-vv).
     *
     * @return bool
     */
    public function isVeryVerbose(): bool
    {
        return $this->output->isVeryVerbose();
    }

    /**
     * Returns whether verbosity is debug (-vvv).
     *
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->output->isDebug();
    }

    /**
     * Get the underlying LaraGram output implementation.
     *
     * @return \LaraGram\Console\Output\OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }
}
