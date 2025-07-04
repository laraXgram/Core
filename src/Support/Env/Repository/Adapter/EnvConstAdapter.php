<?php

declare(strict_types=1);

namespace LaraGram\Support\Env\Repository\Adapter;

use LaraGram\Support\Env\Util\Option;
use LaraGram\Support\Env\Util\Some;

final class EnvConstAdapter implements AdapterInterface
{
    /**
     * Create a new env const adapter instance.
     *
     * @return void
     */
    private function __construct()
    {
        //
    }

    /**
     * Create a new instance of the adapter, if it is available.
     *
     * @return \LaraGram\Support\Env\Util\Option<\LaraGram\Support\Env\Repository\Adapter\AdapterInterface>
     */
    public static function create()
    {
        /** @var \LaraGram\Support\Env\Util\Option<AdapterInterface> */
        return Some::create(new self());
    }

    /**
     * Read an environment variable, if it exists.
     *
     * @param non-empty-string $name
     *
     * @return \LaraGram\Support\Env\Util\Option<string>
     */
    public function read(string $name)
    {
        /** @var \LaraGram\Support\Env\Util\Option<string> */
        return Option::fromArraysValue($_ENV, $name)
            ->filter(static function ($value) {
                return \is_scalar($value);
            })
            ->map(static function ($value) {
                if ($value === false) {
                    return 'false';
                }

                if ($value === true) {
                    return 'true';
                }

                /** @psalm-suppress PossiblyInvalidCast */
                return (string) $value;
            });
    }

    /**
     * Write to an environment variable, if possible.
     *
     * @param non-empty-string $name
     * @param string           $value
     *
     * @return bool
     */
    public function write(string $name, string $value)
    {
        $_ENV[$name] = $value;

        return true;
    }

    /**
     * Delete an environment variable, if possible.
     *
     * @param non-empty-string $name
     *
     * @return bool
     */
    public function delete(string $name)
    {
        unset($_ENV[$name]);

        return true;
    }
}
