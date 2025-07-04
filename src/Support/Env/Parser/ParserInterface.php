<?php

declare(strict_types=1);

namespace LaraGram\Support\Env\Parser;

interface ParserInterface
{
    /**
     * Parse content into an entry array.
     *
     * @param string $content
     *
     * @throws \LaraGram\Support\Env\Exception\InvalidFileException
     *
     * @return \LaraGram\Support\Env\Parser\Entry[]
     */
    public function parse(string $content);
}
