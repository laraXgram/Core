<?php

namespace LaraGram\Contracts\Validation;

use LaraGram\Validation\Validator;

interface ValidatorAwareRule
{
    /**
     * Set the current validator.
     *
     * @param  \LaraGram\Validation\Validator  $validator
     * @return $this
     */
    public function setValidator(Validator $validator);
}
