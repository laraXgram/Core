<?php

namespace LaraGram\Log\Logger;

class DebugLoggerConfigurator
{
    private ?object $processor = null;

    public function __construct(callable $processor, ?bool $enable = null)
    {
        if ($enable ?? !\in_array(\PHP_SAPI, ['cli', 'phpdbg', 'embed'], true)) {
            $this->processor = \is_object($processor) ? $processor : $processor(...);
        }
    }

    public function pushDebugLogger(Logger $logger): void
    {
        if ($this->processor) {
            $logger->pushProcessor($this->processor);
        }
    }

    public static function getDebugLogger(mixed $logger): ?DebugLoggerInterface
    {
        if ($logger instanceof DebugLoggerInterface) {
            return $logger;
        }

        if (!$logger instanceof Logger) {
            return null;
        }

        foreach ($logger->getProcessors() as $processor) {
            if ($processor instanceof DebugLoggerInterface) {
                return $processor;
            }
        }

        return null;
    }
}
