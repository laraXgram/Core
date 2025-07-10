<?php

namespace LaraGram\Validation;

use LaraGram\Contracts\Support\Arrayable;
use LaraGram\Support\Traits\Macroable;
use LaraGram\Validation\Rules\ArrayRule;
use LaraGram\Validation\Rules\Can;
use LaraGram\Validation\Rules\Date;
use LaraGram\Validation\Rules\Dimensions;
use LaraGram\Validation\Rules\Email;
use LaraGram\Validation\Rules\Enum;
use LaraGram\Validation\Rules\ExcludeIf;
use LaraGram\Validation\Rules\Exists;
use LaraGram\Validation\Rules\File;
use LaraGram\Validation\Rules\ImageFile;
use LaraGram\Validation\Rules\In;
use LaraGram\Validation\Rules\NotIn;
use LaraGram\Validation\Rules\Numeric;
use LaraGram\Validation\Rules\ProhibitedIf;
use LaraGram\Validation\Rules\RequiredIf;
use LaraGram\Validation\Rules\Unique;

class Rule
{
    use Macroable;

    /**
     * Get a can constraint builder instance.
     *
     * @param  string  $ability
     * @param  mixed  ...$arguments
     * @return \LaraGram\Validation\Rules\Can
     */
    public static function can($ability, ...$arguments)
    {
        return new Can($ability, $arguments);
    }

    /**
     * Apply the given rules if the given condition is truthy.
     *
     * @param  callable|bool  $condition
     * @param  \LaraGram\Contracts\Validation\ValidationRule|\LaraGram\Contracts\Validation\InvokableRule|\LaraGram\Contracts\Validation\Rule|\Closure|array|string  $rules
     * @param  \LaraGram\Contracts\Validation\ValidationRule|\LaraGram\Contracts\Validation\InvokableRule|\LaraGram\Contracts\Validation\Rule|\Closure|array|string  $defaultRules
     * @return \LaraGram\Validation\ConditionalRules
     */
    public static function when($condition, $rules, $defaultRules = [])
    {
        return new ConditionalRules($condition, $rules, $defaultRules);
    }

    /**
     * Apply the given rules if the given condition is falsy.
     *
     * @param  callable|bool  $condition
     * @param  \LaraGram\Contracts\Validation\ValidationRule|\LaraGram\Contracts\Validation\InvokableRule|\LaraGram\Contracts\Validation\Rule|\Closure|array|string  $rules
     * @param  \LaraGram\Contracts\Validation\ValidationRule|\LaraGram\Contracts\Validation\InvokableRule|\LaraGram\Contracts\Validation\Rule|\Closure|array|string  $defaultRules
     * @return \LaraGram\Validation\ConditionalRules
     */
    public static function unless($condition, $rules, $defaultRules = [])
    {
        return new ConditionalRules($condition, $defaultRules, $rules);
    }

    /**
     * Get an array rule builder instance.
     *
     * @param  array|null  $keys
     * @return \LaraGram\Validation\Rules\ArrayRule
     */
    public static function array($keys = null)
    {
        return new ArrayRule(...func_get_args());
    }

    /**
     * Create a new nested rule set.
     *
     * @param  callable  $callback
     * @return \LaraGram\Validation\NestedRules
     */
    public static function forEach($callback)
    {
        return new NestedRules($callback);
    }

    /**
     * Get a unique constraint builder instance.
     *
     * @param  string  $table
     * @param  string  $column
     * @return \LaraGram\Validation\Rules\Unique
     */
    public static function unique($table, $column = 'NULL')
    {
        return new Unique($table, $column);
    }

    /**
     * Get an exists constraint builder instance.
     *
     * @param  string  $table
     * @param  string  $column
     * @return \LaraGram\Validation\Rules\Exists
     */
    public static function exists($table, $column = 'NULL')
    {
        return new Exists($table, $column);
    }

    /**
     * Get an in rule builder instance.
     *
     * @param  \LaraGram\Contracts\Support\Arrayable|\BackedEnum|\UnitEnum|array|string  $values
     * @return \LaraGram\Validation\Rules\In
     */
    public static function in($values)
    {
        if ($values instanceof Arrayable) {
            $values = $values->toArray();
        }

        return new In(is_array($values) ? $values : func_get_args());
    }

    /**
     * Get a not_in rule builder instance.
     *
     * @param  \LaraGram\Contracts\Support\Arrayable|\BackedEnum|\UnitEnum|array|string  $values
     * @return \LaraGram\Validation\Rules\NotIn
     */
    public static function notIn($values)
    {
        if ($values instanceof Arrayable) {
            $values = $values->toArray();
        }

        return new NotIn(is_array($values) ? $values : func_get_args());
    }

    /**
     * Get a required_if rule builder instance.
     *
     * @param  callable|bool  $callback
     * @return \LaraGram\Validation\Rules\RequiredIf
     */
    public static function requiredIf($callback)
    {
        return new RequiredIf($callback);
    }

    /**
     * Get a exclude_if rule builder instance.
     *
     * @param  callable|bool  $callback
     * @return \LaraGram\Validation\Rules\ExcludeIf
     */
    public static function excludeIf($callback)
    {
        return new ExcludeIf($callback);
    }

    /**
     * Get a prohibited_if rule builder instance.
     *
     * @param  callable|bool  $callback
     * @return \LaraGram\Validation\Rules\ProhibitedIf
     */
    public static function prohibitedIf($callback)
    {
        return new ProhibitedIf($callback);
    }

    /**
     * Get a date rule builder instance.
     *
     * @return \LaraGram\Validation\Rules\Date
     */
    public static function date()
    {
        return new Date;
    }

    /**
     * Get an email rule builder instance.
     *
     * @return \LaraGram\Validation\Rules\Email
     */
    public static function email()
    {
        return new Email;
    }

    /**
     * Get an enum rule builder instance.
     *
     * @param  class-string  $type
     * @return \LaraGram\Validation\Rules\Enum
     */
    public static function enum($type)
    {
        return new Enum($type);
    }

    /**
     * Get a file rule builder instance.
     *
     * @return \LaraGram\Validation\Rules\File
     */
    public static function file()
    {
        return new File;
    }

    /**
     * Get an image file rule builder instance.
     *
     * @return \LaraGram\Validation\Rules\ImageFile
     */
    public static function imageFile()
    {
        return new ImageFile;
    }

    /**
     * Get a dimensions rule builder instance.
     *
     * @param  array  $constraints
     * @return \LaraGram\Validation\Rules\Dimensions
     */
    public static function dimensions(array $constraints = [])
    {
        return new Dimensions($constraints);
    }

    /**
     * Get a numeric rule builder instance.
     *
     * @return \LaraGram\Validation\Rules\Numeric
     */
    public static function numeric()
    {
        return new Numeric;
    }
}
