<?php

namespace LaraGram\Console\Input;

interface InputAwareInterface
{
    /**
     * Sets the Console Input.
     */
    public function setInput(InputInterface $input): void;
}
