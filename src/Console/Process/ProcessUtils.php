<?php

namespace LaraGram\Console\Process;

use LaraGram\Console\Process\Exception\InvalidArgumentException;

class ProcessUtils
{
    /**
     * This class should not be instantiated.
     */
    private function __construct()
    {
    }

    /**
     * Validates and normalizes a Process input.
     *
     * @param string $caller The name of method call that validates the input
     * @param mixed  $input  The input to validate
     *
     * @throws InvalidArgumentException In case the input is not valid
     */
    public static function validateInput(string $caller, mixed $input): mixed
    {
        if (null !== $input) {
            if (\is_resource($input)) {
                return $input;
            }
            if (\is_scalar($input)) {
                return (string) $input;
            }
            if ($input instanceof Process) {
                return $input->getIterator($input::ITER_SKIP_ERR);
            }
            if ($input instanceof \Iterator) {
                return $input;
            }
            if ($input instanceof \Traversable) {
                return new \IteratorIterator($input);
            }

            throw new InvalidArgumentException(\sprintf('"%s" only accepts strings, Traversable objects or stream resources.', $caller));
        }

        return $input;
    }
}
