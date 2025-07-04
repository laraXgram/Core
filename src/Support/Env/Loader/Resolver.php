<?php

declare(strict_types=1);

namespace LaraGram\Support\Env\Loader;

use LaraGram\Support\Env\Parser\Value;
use LaraGram\Support\Env\Repository\RepositoryInterface;
use LaraGram\Support\Env\Util\Option;
use LaraGram\Support\Env\Util\Regex;
use LaraGram\Support\Env\Util\Str;

final class Resolver
{
    /**
     * This class is a singleton.
     *
     * @codeCoverageIgnore
     *
     * @return void
     */
    private function __construct()
    {
        //
    }

    /**
     * Resolve the nested variables in the given value.
     *
     * Replaces ${varname} patterns in the allowed positions in the variable
     * value by an existing environment variable.
     *
     * @param \LaraGram\Support\Env\Repository\RepositoryInterface $repository
     * @param \LaraGram\Support\Env\Parser\Value                   $value
     *
     * @return string
     */
    public static function resolve(RepositoryInterface $repository, Value $value)
    {
        return \array_reduce($value->getVars(), static function (string $s, int $i) use ($repository) {
            return Str::substr($s, 0, $i).self::resolveVariable($repository, Str::substr($s, $i));
        }, $value->getChars());
    }

    /**
     * Resolve a single nested variable.
     *
     * @param \LaraGram\Support\Env\Repository\RepositoryInterface $repository
     * @param string                                 $str
     *
     * @return string
     */
    private static function resolveVariable(RepositoryInterface $repository, string $str)
    {
        return Regex::replaceCallback(
            '/\A\${([a-zA-Z0-9_.]+)}/',
            static function (array $matches) use ($repository) {
                /** @var string */
                return Option::fromValue($repository->get($matches[1]))->getOrElse($matches[0]);
            },
            $str,
            1
        )->success()->getOrElse($str);
    }
}
