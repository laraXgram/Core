<?php

namespace LaraGram\Support\Uri\Exceptions;

use LaraGram\Support\Uri\Contracts\UriException;
use RuntimeException;

class MissingFeature extends RuntimeException implements UriException
{
}
