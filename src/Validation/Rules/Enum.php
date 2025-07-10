<?php

namespace LaraGram\Validation\Rules;

use LaraGram\Contracts\Support\Arrayable;
use LaraGram\Contracts\Validation\Rule;
use LaraGram\Contracts\Validation\ValidatorAwareRule;
use LaraGram\Support\Arr;
use LaraGram\Support\Traits\Conditionable;
use TypeError;

class Enum implements Rule, ValidatorAwareRule
{
    use Conditionable;

    /**
     * The type of the enum.
     *
     * @var class-string
     */
    protected $type;

    /**
     * The current validator instance.
     *
     * @var \LaraGram\Validation\Validator
     */
    protected $validator;

    /**
     * The cases that should be considered valid.
     *
     * @var array
     */
    protected $only = [];

    /**
     * The cases that should be considered invalid.
     *
     * @var array
     */
    protected $except = [];

    /**
     * Create a new rule instance.
     *
     * @param  class-string  $type
     * @return void
     */
    public function __construct($type)
    {
        $this->type = $type;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if ($value instanceof $this->type) {
            return $this->isDesirable($value);
        }

        if (is_null($value) || ! enum_exists($this->type) || ! method_exists($this->type, 'tryFrom')) {
            return false;
        }

        try {
            $value = $this->type::tryFrom($value);

            return ! is_null($value) && $this->isDesirable($value);
        } catch (TypeError) {
            return false;
        }
    }

    /**
     * Specify the cases that should be considered valid.
     *
     * @param  \UnitEnum[]|\UnitEnum|\LaraGram\Contracts\Support\Arrayable<array-key, \UnitEnum>  $values
     * @return $this
     */
    public function only($values)
    {
        $this->only = $values instanceof Arrayable ? $values->toArray() : Arr::wrap($values);

        return $this;
    }

    /**
     * Specify the cases that should be considered invalid.
     *
     * @param  \UnitEnum[]|\UnitEnum|\LaraGram\Contracts\Support\Arrayable<array-key, \UnitEnum>  $values
     * @return $this
     */
    public function except($values)
    {
        $this->except = $values instanceof Arrayable ? $values->toArray() : Arr::wrap($values);

        return $this;
    }

    /**
     * Determine if the given case is a valid case based on the only / except values.
     *
     * @param  mixed  $value
     * @return bool
     */
    protected function isDesirable($value)
    {
        return match (true) {
            ! empty($this->only) => in_array(needle: $value, haystack: $this->only, strict: true),
            ! empty($this->except) => ! in_array(needle: $value, haystack: $this->except, strict: true),
            default => true,
        };
    }

    /**
     * Get the validation error message.
     *
     * @return array
     */
    public function message()
    {
        $message = $this->validator->getTranslator()->get('validation.enum');

        return $message === 'validation.enum'
            ? ['The selected :attribute is invalid.']
            : $message;
    }

    /**
     * Set the current validator.
     *
     * @param  \LaraGram\Validation\Validator  $validator
     * @return $this
     */
    public function setValidator($validator)
    {
        $this->validator = $validator;

        return $this;
    }
}
