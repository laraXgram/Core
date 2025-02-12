<?php

namespace LaraGram\Contracts\Log;

interface LoggerAwareInterface
{
    /**
     * Sets a logger instance on the object.
     */
    public function setLogger(LoggerInterface $logger): void;
}
