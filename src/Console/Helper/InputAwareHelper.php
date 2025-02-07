<?php

namespace LaraGram\Console\Helper;

use LaraGram\Console\Input\InputAwareInterface;
use LaraGram\Console\Input\InputInterface;

abstract class InputAwareHelper extends Helper implements InputAwareInterface
{
    protected InputInterface $input;

    public function setInput(InputInterface $input): void
    {
        $this->input = $input;
    }
}
