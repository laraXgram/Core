<?php

namespace LaraGram\Support\Uri\Exceptions;

use BackedEnum;
use LaraGram\Support\Uri\Idna\Error;
use LaraGram\Support\Uri\Idna\Result;
use Stringable;

final class ConversionFailed extends SyntaxError
{
    private function __construct(
        string $message,
        private readonly string $host,
        private readonly Result $result
    ) {
        parent::__construct($message);
    }

    public static function dueToIdnError(BackedEnum|Stringable|string $host, Result $result): self
    {
        $reasons = array_map(fn (Error $error): string => $error->description(), $result->errors());

        if ($host instanceof BackedEnum) {
            $host = (string) $host->value;
        }

        return new self('Host `'.$host.'` is invalid: '.implode('; ', $reasons).'.', (string) $host, $result);
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getResult(): Result
    {
        return $this->result;
    }
}
