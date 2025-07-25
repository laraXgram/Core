<?php

namespace LaraGram\Validation\Concerns\EmailValidator\Validation;

use LaraGram\Validation\Concerns\EmailValidator\EmailLexer;
use LaraGram\Validation\Concerns\EmailValidator\Result\InvalidEmail;
use LaraGram\Validation\Concerns\EmailValidator\Result\Reason\RFCWarnings;

class NoRFCWarningsValidation extends RFCValidation
{
    /**
     * @var InvalidEmail|null
     */
    private $error;

    /**
     * {@inheritdoc}
     */
    public function isValid(string $email, EmailLexer $emailLexer) : bool
    {
        if (!parent::isValid($email, $emailLexer)) {
            return false;
        }

        if (empty($this->getWarnings())) {
            return true;
        }

        $this->error = new InvalidEmail(new RFCWarnings(), '');

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getError() : ?InvalidEmail
    {
        return $this->error ?: parent::getError();
    }
}
