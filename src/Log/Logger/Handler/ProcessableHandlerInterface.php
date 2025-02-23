<?php declare(strict_types=1);

namespace LaraGram\Log\Logger\Handler;

use LaraGram\Log\Logger\Processor\ProcessorInterface;
use LaraGram\Log\Logger\LogRecord;

interface ProcessableHandlerInterface
{
    /**
     * Adds a processor in the stack.
     *
     * @phpstan-param ProcessorInterface|(callable(LogRecord): LogRecord) $callback
     *
     * @param  ProcessorInterface|callable $callback
     * @return HandlerInterface            self
     */
    public function pushProcessor(callable $callback): HandlerInterface;

    /**
     * Removes the processor on top of the stack and returns it.
     *
     * @phpstan-return ProcessorInterface|(callable(LogRecord): LogRecord) $callback
     *
     * @throws \LogicException             In case the processor stack is empty
     * @return callable|ProcessorInterface
     */
    public function popProcessor(): callable;
}
