<?php declare(strict_types=1);

namespace LaraGram\Log\Logger\Handler;

use LaraGram\Log\Logger\ResettableInterface;
use LaraGram\Log\Logger\Processor\ProcessorInterface;
use LaraGram\Log\Logger\LogRecord;

trait ProcessableHandlerTrait
{
    /**
     * @var callable[]
     * @phpstan-var array<(callable(LogRecord): LogRecord)|ProcessorInterface>
     */
    protected array $processors = [];

    /**
     * @inheritDoc
     */
    public function pushProcessor(callable $callback): HandlerInterface
    {
        array_unshift($this->processors, $callback);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function popProcessor(): callable
    {
        if (\count($this->processors) === 0) {
            throw new \LogicException('You tried to pop from an empty processor stack.');
        }

        return array_shift($this->processors);
    }

    protected function processRecord(LogRecord $record): LogRecord
    {
        foreach ($this->processors as $processor) {
            $record = $processor($record);
        }

        return $record;
    }

    protected function resetProcessors(): void
    {
        foreach ($this->processors as $processor) {
            if ($processor instanceof ResettableInterface) {
                $processor->reset();
            }
        }
    }
}
