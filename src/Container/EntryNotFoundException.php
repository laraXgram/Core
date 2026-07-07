<?php

namespace LaraGram\Container;

use Exception;
use LaraGram\Contracts\Container\NotFoundExceptionInterface;

class EntryNotFoundException extends Exception implements NotFoundExceptionInterface
{
    //
}
