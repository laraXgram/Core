<?php

namespace LaraGram\Validation\Concerns\EmailValidator\Warning;

use LaraGram\Validation\Concerns\EmailValidator\EmailParser;

class EmailTooLong extends Warning
{
    public const CODE = 66;

    public function __construct()
    {
        $this->message = 'Email is too long, exceeds ' . EmailParser::EMAIL_MAX_LENGTH;
    }
}
