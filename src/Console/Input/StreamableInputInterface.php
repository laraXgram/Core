<?php

namespace LaraGram\Console\Input;

interface StreamableInputInterface extends InputInterface
{
    /**
     * Sets the input stream to read from when interacting with the user.
     *
     * This is mainly useful for testing purpose.
     *
     * @param resource $stream The input stream
     */
    public function setStream($stream): void;

    /**
     * Returns the input stream.
     *
     * @return resource|null
     */
    public function getStream();
}
