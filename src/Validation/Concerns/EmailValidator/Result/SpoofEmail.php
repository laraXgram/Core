<?php
namespace LaraGram\Validation\Concerns\EmailValidator\Result;

use LaraGram\Validation\Concerns\EmailValidator\Result\Reason\SpoofEmail as ReasonSpoofEmail;

class SpoofEmail extends InvalidEmail
{
    public function __construct()
    {
        $this->reason = new ReasonSpoofEmail();
        parent::__construct($this->reason, '');
    }
}
