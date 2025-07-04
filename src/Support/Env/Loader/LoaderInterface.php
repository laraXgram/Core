<?php

declare(strict_types=1);

namespace LaraGram\Support\Env\Loader;

use LaraGram\Support\Env\Repository\RepositoryInterface;

interface LoaderInterface
{
    /**
     * Load the given entries into the repository.
     *
     * @param \LaraGram\Support\Env\Repository\RepositoryInterface $repository
     * @param \LaraGram\Support\Env\Parser\Entry[]                 $entries
     *
     * @return array<string, string|null>
     */
    public function load(RepositoryInterface $repository, array $entries);
}
