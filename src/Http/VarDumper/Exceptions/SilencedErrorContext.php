<?php

namespace LaraGram\Http\VarDumper\Exceptions;

class SilencedErrorContext implements \JsonSerializable
{
    public int $count = 1;

    public function __construct(
        private int $severity,
        private string $file,
        private int $line,
        private array $trace = [],
        int $count = 1,
    ) {
        $this->count = $count;
    }

    public function getSeverity(): int
    {
        return $this->severity;
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function getLine(): int
    {
        return $this->line;
    }

    public function getTrace(): array
    {
        return $this->trace;
    }

    public function jsonSerialize(): array
    {
        return [
            'severity' => $this->severity,
            'file' => $this->file,
            'line' => $this->line,
            'trace' => $this->trace,
            'count' => $this->count,
        ];
    }
}
