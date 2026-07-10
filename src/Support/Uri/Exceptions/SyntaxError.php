<?php

namespace LaraGram\Support\Uri\Exceptions;

use InvalidArgumentException;
use LaraGram\Support\Uri\Contracts\UriException;

class SyntaxError extends InvalidArgumentException implements UriException
{
}
