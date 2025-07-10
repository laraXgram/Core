<?php

namespace LaraGram\Validation\Concerns\EmailValidator\Warning;

class TLD extends Warning
{
    public const CODE = 9;

    public function __construct()
    {
        $this->message = "RFC5321, TLD";
    }
}
