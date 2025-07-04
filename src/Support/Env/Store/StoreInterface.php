<?php

declare(strict_types=1);

namespace LaraGram\Support\Env\Store;

interface StoreInterface
{
    /**
     * Read the content of the environment file(s).
     *
     * @throws \LaraGram\Support\Env\Exception\InvalidEncodingException|\LaraGram\Support\Env\Exception\InvalidPathException
     *
     * @return string
     */
    public function read();
}
