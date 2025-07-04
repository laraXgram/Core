<?php

declare(strict_types=1);

namespace LaraGram\Support\Env\Repository\Adapter;

interface ReaderInterface
{
    /**
     * Read an environment variable, if it exists.
     *
     * @param non-empty-string $name
     *
     * @return \LaraGram\Support\Env\Util\Option<string>
     */
    public function read(string $name);
}
