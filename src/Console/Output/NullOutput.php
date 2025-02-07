<?php

namespace LaraGram\Console\Output;

use LaraGram\Console\Formatter\NullOutputFormatter;
use LaraGram\Console\Formatter\OutputFormatterInterface;

class NullOutput implements OutputInterface
{
    private NullOutputFormatter $formatter;

    public function setFormatter(OutputFormatterInterface $formatter): void
    {
        // do nothing
    }

    public function getFormatter(): OutputFormatterInterface
    {
        // to comply with the interface we must return a OutputFormatterInterface
        return $this->formatter ??= new NullOutputFormatter();
    }

    public function setDecorated(bool $decorated): void
    {
        // do nothing
    }

    public function isDecorated(): bool
    {
        return false;
    }

    public function setVerbosity(int $level): void
    {
        // do nothing
    }

    public function getVerbosity(): int
    {
        return self::VERBOSITY_SILENT;
    }

    public function isSilent(): bool
    {
        return true;
    }

    public function isQuiet(): bool
    {
        return false;
    }

    public function isVerbose(): bool
    {
        return false;
    }

    public function isVeryVerbose(): bool
    {
        return false;
    }

    public function isDebug(): bool
    {
        return false;
    }

    public function writeln(string|iterable $messages, int $options = self::OUTPUT_NORMAL): void
    {
        // do nothing
    }

    public function write(string|iterable $messages, bool $newline = false, int $options = self::OUTPUT_NORMAL): void
    {
        // do nothing
    }
}
