<?php

declare(strict_types=1);

namespace LaraGram\Support\Env\Repository\Adapter;

use LaraGram\Support\Env\Util\None;

final class MultiReader implements ReaderInterface
{
    /**
     * The set of readers to use.
     *
     * @var \LaraGram\Support\Env\Repository\Adapter\ReaderInterface[]
     */
    private $readers;

    /**
     * Create a new multi-reader instance.
     *
     * @param \LaraGram\Support\Env\Repository\Adapter\ReaderInterface[] $readers
     *
     * @return void
     */
    public function __construct(array $readers)
    {
        $this->readers = $readers;
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
        foreach ($this->readers as $reader) {
            $result = $reader->read($name);
            if ($result->isDefined()) {
                return $result;
            }
        }

        return None::create();
    }
}
