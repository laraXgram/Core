<?php

namespace LaraGram\Request;

use LaraGram\Laraquest\Methode;
use LaraGram\Laraquest\Updates;
use LaraGram\Support\Traits\Macroable;

class Request
{
    use Methode, Updates, Macroable;
}