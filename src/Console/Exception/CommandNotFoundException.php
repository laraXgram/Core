<?php

namespace LaraGram\Console\Exception;

class CommandNotFoundException extends \InvalidArgumentException implements ExceptionInterface
{
    /**
     * @param string          $message      Exception message to throw
     * @param string[]        $alternatives List of similar defined names
     * @param int             $code         Exception code
     * @param \Throwable|null $previous     Previous exception used for the exception chaining
     */
    public function __construct(
        string $message,
        private array $alternatives = [],
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string[]
     */
    public function getAlternatives(): array
    {
        return $this->alternatives;
    }
}
