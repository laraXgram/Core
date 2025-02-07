<?php

namespace LaraGram\Contracts\Queue;

interface Factory
{
    public function connection($name = null);
}
