<?php

namespace LaraGram\Contracts\Log;

class NullLogger extends AbstractLogger
{
    /**
     * Logs with an arbitrary level.
     *
     * @param mixed[] $context
     *
     * @throws \LaraGram\Contracts\Log\InvalidArgumentException
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        // noop
    }
}
