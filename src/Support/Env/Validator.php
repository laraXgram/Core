<?php

declare(strict_types=1);

namespace LaraGram\Support\Env;

use LaraGram\Support\Env\Exception\ValidationException;
use LaraGram\Support\Env\Repository\RepositoryInterface;
use LaraGram\Support\Env\Util\Regex;
use LaraGram\Support\Env\Util\Str;

class Validator
{
    /**
     * The environment repository instance.
     *
     * @var \LaraGram\Support\Env\Repository\RepositoryInterface
     */
    private $repository;

    /**
     * The variables to validate.
     *
     * @var string[]
     */
    private $variables;

    /**
     * Create a new validator instance.
     *
     * @param \LaraGram\Support\Env\Repository\RepositoryInterface $repository
     * @param string[]                               $variables
     *
     * @return void
     */
    public function __construct(RepositoryInterface $repository, array $variables)
    {
        $this->repository = $repository;
        $this->variables = $variables;
    }

    /**
     * Assert that each variable is present.
     *
     * @throws \LaraGram\Support\Env\Exception\ValidationException
     *
     * @return \LaraGram\Support\Env\Validator
     */
    public function required()
    {
        return $this->assert(
            static function (?string $value) {
                return $value !== null;
            },
            'is missing'
        );
    }

    /**
     * Assert that each variable is not empty.
     *
     * @throws \LaraGram\Support\Env\Exception\ValidationException
     *
     * @return \LaraGram\Support\Env\Validator
     */
    public function notEmpty()
    {
        return $this->assertNullable(
            static function (string $value) {
                return Str::len(\trim($value)) > 0;
            },
            'is empty'
        );
    }

    /**
     * Assert that each specified variable is an integer.
     *
     * @throws \LaraGram\Support\Env\Exception\ValidationException
     *
     * @return \LaraGram\Support\Env\Validator
     */
    public function isInteger()
    {
        return $this->assertNullable(
            static function (string $value) {
                return \ctype_digit($value);
            },
            'is not an integer'
        );
    }

    /**
     * Assert that each specified variable is a boolean.
     *
     * @throws \LaraGram\Support\Env\Exception\ValidationException
     *
     * @return \LaraGram\Support\Env\Validator
     */
    public function isBoolean()
    {
        return $this->assertNullable(
            static function (string $value) {
                if ($value === '') {
                    return false;
                }

                return \filter_var($value, \FILTER_VALIDATE_BOOLEAN, \FILTER_NULL_ON_FAILURE) !== null;
            },
            'is not a boolean'
        );
    }

    /**
     * Assert that each variable is amongst the given choices.
     *
     * @param string[] $choices
     *
     * @throws \LaraGram\Support\Env\Exception\ValidationException
     *
     * @return \LaraGram\Support\Env\Validator
     */
    public function allowedValues(array $choices)
    {
        return $this->assertNullable(
            static function (string $value) use ($choices) {
                return \in_array($value, $choices, true);
            },
            \sprintf('is not one of [%s]', \implode(', ', $choices))
        );
    }

    /**
     * Assert that each variable matches the given regular expression.
     *
     * @param string $regex
     *
     * @throws \LaraGram\Support\Env\Exception\ValidationException
     *
     * @return \LaraGram\Support\Env\Validator
     */
    public function allowedRegexValues(string $regex)
    {
        return $this->assertNullable(
            static function (string $value) use ($regex) {
                return Regex::matches($regex, $value)->success()->getOrElse(false);
            },
            \sprintf('does not match "%s"', $regex)
        );
    }

    /**
     * Assert that the callback returns true for each variable.
     *
     * @param callable(?string):bool $callback
     * @param string                 $message
     *
     * @throws \LaraGram\Support\Env\Exception\ValidationException
     *
     * @return \LaraGram\Support\Env\Validator
     */
    public function assert(callable $callback, string $message)
    {
        $failing = [];

        foreach ($this->variables as $variable) {
            if ($callback($this->repository->get($variable)) === false) {
                $failing[] = \sprintf('%s %s', $variable, $message);
            }
        }

        if (\count($failing) > 0) {
            throw new ValidationException(\sprintf(
                'One or more environment variables failed assertions: %s.',
                \implode(', ', $failing)
            ));
        }

        return $this;
    }

    /**
     * Assert that the callback returns true for each variable.
     *
     * Skip checking null variable values.
     *
     * @param callable(string):bool $callback
     * @param string                $message
     *
     * @throws \LaraGram\Support\Env\Exception\ValidationException
     *
     * @return \LaraGram\Support\Env\Validator
     */
    public function assertNullable(callable $callback, string $message)
    {
        return $this->assert(
            static function (?string $value) use ($callback) {
                if ($value === null) {
                    return true;
                }

                return $callback($value);
            },
            $message
        );
    }
}
