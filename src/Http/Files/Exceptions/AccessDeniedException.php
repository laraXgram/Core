<?php

namespace LaraGram\Http\Files\Exceptions;

class AccessDeniedException extends FileException
{
    public function __construct(string $path)
    {
        parent::__construct(\sprintf('The file %s could not be accessed', $path));
    }
}
