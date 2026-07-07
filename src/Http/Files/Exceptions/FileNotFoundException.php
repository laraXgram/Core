<?php

namespace LaraGram\Http\Files\Exceptions;

class FileNotFoundException extends FileException
{
    public function __construct(string $path)
    {
        parent::__construct(\sprintf('The file "%s" does not exist', $path));
    }
}
