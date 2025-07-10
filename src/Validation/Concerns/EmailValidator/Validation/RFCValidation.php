<?php

namespace LaraGram\Validation\Concerns\EmailValidator\Validation;

use LaraGram\Validation\Concerns\EmailValidator\EmailLexer;
use LaraGram\Validation\Concerns\EmailValidator\EmailParser;
use LaraGram\Validation\Concerns\EmailValidator\Result\InvalidEmail;
use LaraGram\Validation\Concerns\EmailValidator\Result\Reason\ExceptionFound;
use LaraGram\Validation\Concerns\EmailValidator\Warning\Warning;

class RFCValidation implements EmailValidation
{
    /**
     * @var Warning[]
     */
    private array $warnings = [];

    /**
     * @var ?InvalidEmail
     */
    private $error;

    public function isValid(string $email, EmailLexer $emailLexer): bool
    {
        $parser = new EmailParser($emailLexer);
        try {
            $result = $parser->parse($email);
            $this->warnings = $parser->getWarnings();
            if ($result->isInvalid()) {
                /** @psalm-suppress PropertyTypeCoercion */
                $this->error = $result;
                return false;
            }
        } catch (\Exception $invalid) {
            $this->error = new InvalidEmail(new ExceptionFound($invalid), '');
            return false;
        }

        return true;
    }

    public function getError(): ?InvalidEmail
    {
        return $this->error;
    }

    /**
     * @return Warning[]
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }
}
