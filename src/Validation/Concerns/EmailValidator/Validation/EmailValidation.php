<?php

namespace LaraGram\Validation\Concerns\EmailValidator\Validation;

use LaraGram\Validation\Concerns\EmailValidator\EmailLexer;
use LaraGram\Validation\Concerns\EmailValidator\Result\InvalidEmail;
use LaraGram\Validation\Concerns\EmailValidator\Warning\Warning;

interface EmailValidation
{
    /**
     * Returns true if the given email is valid.
     *
     * @param string     $email      The email you want to validate.
     * @param EmailLexer $emailLexer The email lexer.
     *
     * @return bool
     */
    public function isValid(string $email, EmailLexer $emailLexer) : bool;

    /**
     * Returns the validation error.
     *
     * @return InvalidEmail|null
     */
    public function getError() : ?InvalidEmail;

    /**
     * Returns the validation warnings.
     *
     * @return Warning[]
     */
    public function getWarnings() : array;
}
