<?php

namespace LaraGram\Console\Output;

use LaraGram\Console\Formatter\OutputFormatterInterface;

class ConsoleOutput extends StreamOutput implements ConsoleOutputInterface
{
    private OutputInterface $stderr;
    private array $consoleSectionOutputs = [];

    /**
     * @param int                           $verbosity The verbosity level (one of the VERBOSITY constants in OutputInterface)
     * @param bool|null                     $decorated Whether to decorate messages (null for auto-guessing)
     * @param OutputFormatterInterface|null $formatter Output formatter instance (null to use default OutputFormatter)
     */
    public function __construct(int $verbosity = self::VERBOSITY_NORMAL, ?bool $decorated = null, ?OutputFormatterInterface $formatter = null)
    {
        parent::__construct($this->openOutputStream(), $verbosity, $decorated, $formatter);

        if (null === $formatter) {
            // for BC reasons, stdErr has it own Formatter only when user don't inject a specific formatter.
            $this->stderr = new StreamOutput($this->openErrorStream(), $verbosity, $decorated);

            return;
        }

        $actualDecorated = $this->isDecorated();
        $this->stderr = new StreamOutput($this->openErrorStream(), $verbosity, $decorated, $this->getFormatter());

        if (null === $decorated) {
            $this->setDecorated($actualDecorated && $this->stderr->isDecorated());
        }
    }

    /**
     * Creates a new output section.
     */
    public function section(): ConsoleSectionOutput
    {
        return new ConsoleSectionOutput($this->getStream(), $this->consoleSectionOutputs, $this->getVerbosity(), $this->isDecorated(), $this->getFormatter());
    }

    public function setDecorated(bool $decorated): void
    {
        parent::setDecorated($decorated);
        $this->stderr->setDecorated($decorated);
    }

    public function setFormatter(OutputFormatterInterface $formatter): void
    {
        parent::setFormatter($formatter);
        $this->stderr->setFormatter($formatter);
    }

    public function setVerbosity(int $level): void
    {
        parent::setVerbosity($level);
        $this->stderr->setVerbosity($level);
    }

    public function getErrorOutput(): OutputInterface
    {
        return $this->stderr;
    }

    public function setErrorOutput(OutputInterface $error): void
    {
        $this->stderr = $error;
    }

    /**
     * Returns true if current environment supports writing console output to
     * STDOUT.
     */
    protected function hasStdoutSupport(): bool
    {
        return false === $this->isRunningOS400();
    }

    /**
     * Returns true if current environment supports writing console output to
     * STDERR.
     */
    protected function hasStderrSupport(): bool
    {
        return false === $this->isRunningOS400();
    }

    /**
     * Checks if current executing environment is IBM iSeries (OS400), which
     * doesn't properly convert character-encodings between ASCII to EBCDIC.
     */
    private function isRunningOS400(): bool
    {
        $checks = [
            \function_exists('php_uname') ? php_uname('s') : '',
            getenv('OSTYPE'),
            \PHP_OS,
        ];

        return false !== stripos(implode(';', $checks), 'OS400');
    }

    /**
     * @return resource
     */
    private function openOutputStream()
    {
        if (!$this->hasStdoutSupport()) {
            return fopen('php://output', 'w');
        }

        // Use STDOUT when possible to prevent from opening too many file descriptors
        return \defined('STDOUT') ? \STDOUT : (@fopen('php://stdout', 'w') ?: fopen('php://output', 'w'));
    }

    /**
     * @return resource
     */
    private function openErrorStream()
    {
        if (!$this->hasStderrSupport()) {
            return fopen('php://output', 'w');
        }

        // Use STDERR when possible to prevent from opening too many file descriptors
        return \defined('STDERR') ? \STDERR : (@fopen('php://stderr', 'w') ?: fopen('php://output', 'w'));
    }
}
