<?php

namespace LaraGram\Listening\Exceptions;

class MethodNotAllowedException extends \RuntimeException implements ExceptionInterface
{
    protected array $allowedMethods = [];

    /**
     * @param string[] $allowedMethods
     */
    public function __construct(array $allowedMethods, string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        $this->allowedMethods = array_map('strtoupper', $allowedMethods);

        parent::__construct($message, $code, $previous);
    }

    /**
     * Gets the allowed HTTP methods.
     *
     * @return string[]
     */
    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }
}
