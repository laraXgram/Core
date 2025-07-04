<?php

declare(strict_types=1);

namespace LaraGram\Support\Env\Repository\Adapter;

interface AdapterInterface extends ReaderInterface, WriterInterface
{
    /**
     * Create a new instance of the adapter, if it is available.
     *
     * @return \LaraGram\Support\Env\Util\Option<\LaraGram\Support\Env\Repository\Adapter\AdapterInterface>
     */
    public static function create();
}
