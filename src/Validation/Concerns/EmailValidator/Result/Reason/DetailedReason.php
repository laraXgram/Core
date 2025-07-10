<?php

namespace LaraGram\Validation\Concerns\EmailValidator\Result\Reason;

abstract class DetailedReason implements Reason
{
    protected $detailedDescription;

    public function __construct(string $details)
    {
        $this->detailedDescription = $details;
    }
}
