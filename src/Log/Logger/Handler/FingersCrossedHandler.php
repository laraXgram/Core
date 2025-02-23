<?php declare(strict_types=1);

namespace LaraGram\Log\Logger\Handler;

use Closure;
use LaraGram\Log\Logger\Handler\FingersCrossed\ErrorLevelActivationStrategy;
use LaraGram\Log\Logger\Handler\FingersCrossed\ActivationStrategyInterface;
use LaraGram\Log\Logger\Level;
use LaraGram\Log\Logger\Logger;
use LaraGram\Log\Logger\ResettableInterface;
use LaraGram\Log\Logger\Formatter\FormatterInterface;
use LaraGram\Log\LogLevel;
use LaraGram\Log\Logger\LogRecord;

class FingersCrossedHandler extends Handler implements ProcessableHandlerInterface, ResettableInterface, FormattableHandlerInterface
{
    use ProcessableHandlerTrait;

    /**
     * Handler or factory Closure($record, $this)
     *
     * @phpstan-var (Closure(LogRecord|null, HandlerInterface): HandlerInterface)|HandlerInterface
     */
    protected Closure|HandlerInterface $handler;

    protected ActivationStrategyInterface $activationStrategy;

    protected bool $buffering = true;

    protected int $bufferSize;

    /** @var LogRecord[] */
    protected array $buffer = [];

    protected bool $stopBuffering;

    protected Level|null $passthruLevel = null;

    protected bool $bubble;

    /**
     * @phpstan-param (Closure(LogRecord|null, HandlerInterface): HandlerInterface)|HandlerInterface $handler
     *
     * @param Closure|HandlerInterface          $handler            Handler or factory Closure($record|null, $fingersCrossedHandler).
     * @param int|string|Level|LogLevel::*|null $activationStrategy Strategy which determines when this handler takes action, or a level name/value at which the handler is activated
     * @param int                               $bufferSize         How many entries should be buffered at most, beyond that the oldest items are removed from the buffer.
     * @param bool                              $bubble             Whether the messages that are handled can bubble up the stack or not
     * @param bool                              $stopBuffering      Whether the handler should stop buffering after being triggered (default true)
     * @param int|string|Level|LogLevel::*|null $passthruLevel      Minimum level to always flush to handler on close, even if strategy not triggered
     *
     * @phpstan-param value-of<Level::VALUES>|value-of<Level::NAMES>|Level|LogLevel::*|ActivationStrategyInterface|null $activationStrategy
     * @phpstan-param value-of<Level::VALUES>|value-of<Level::NAMES>|Level|LogLevel::*|null $passthruLevel
     */
    public function __construct(Closure|HandlerInterface $handler, int|string|Level|ActivationStrategyInterface|null $activationStrategy = null, int $bufferSize = 0, bool $bubble = true, bool $stopBuffering = true, int|string|Level|null $passthruLevel = null)
    {
        if (null === $activationStrategy) {
            $activationStrategy = new ErrorLevelActivationStrategy(Level::Warning);
        }

        // convert simple int activationStrategy to an object
        if (!$activationStrategy instanceof ActivationStrategyInterface) {
            $activationStrategy = new ErrorLevelActivationStrategy($activationStrategy);
        }

        $this->handler = $handler;
        $this->activationStrategy = $activationStrategy;
        $this->bufferSize = $bufferSize;
        $this->bubble = $bubble;
        $this->stopBuffering = $stopBuffering;

        if ($passthruLevel !== null) {
            $this->passthruLevel = Logger::toLoggerLevel($passthruLevel);
        }
    }

    /**
     * @inheritDoc
     */
    public function isHandling(LogRecord $record): bool
    {
        return true;
    }

    /**
     * Manually activate this logger regardless of the activation strategy
     */
    public function activate(): void
    {
        if ($this->stopBuffering) {
            $this->buffering = false;
        }

        $this->getHandler(end($this->buffer) ?: null)->handleBatch($this->buffer);
        $this->buffer = [];
    }

    /**
     * @inheritDoc
     */
    public function handle(LogRecord $record): bool
    {
        if (\count($this->processors) > 0) {
            $record = $this->processRecord($record);
        }

        if ($this->buffering) {
            $this->buffer[] = $record;
            if ($this->bufferSize > 0 && \count($this->buffer) > $this->bufferSize) {
                array_shift($this->buffer);
            }
            if ($this->activationStrategy->isHandlerActivated($record)) {
                $this->activate();
            }
        } else {
            $this->getHandler($record)->handle($record);
        }

        return false === $this->bubble;
    }

    /**
     * @inheritDoc
     */
    public function close(): void
    {
        $this->flushBuffer();

        $this->getHandler()->close();
    }

    public function reset(): void
    {
        $this->flushBuffer();

        $this->resetProcessors();

        if ($this->getHandler() instanceof ResettableInterface) {
            $this->getHandler()->reset();
        }
    }

    /**
     * Clears the buffer without flushing any messages down to the wrapped handler.
     *
     * It also resets the handler to its initial buffering state.
     */
    public function clear(): void
    {
        $this->buffer = [];
        $this->reset();
    }

    /**
     * Resets the state of the handler. Stops forwarding records to the wrapped handler.
     */
    private function flushBuffer(): void
    {
        if (null !== $this->passthruLevel) {
            $passthruLevel = $this->passthruLevel;
            $this->buffer = array_filter($this->buffer, static function ($record) use ($passthruLevel) {
                return $passthruLevel->includes($record->level);
            });
            if (\count($this->buffer) > 0) {
                $this->getHandler(end($this->buffer))->handleBatch($this->buffer);
            }
        }

        $this->buffer = [];
        $this->buffering = true;
    }

    /**
     * Return the nested handler
     *
     * If the handler was provided as a factory, this will trigger the handler's instantiation.
     */
    public function getHandler(LogRecord|null $record = null): HandlerInterface
    {
        if (!$this->handler instanceof HandlerInterface) {
            $handler = ($this->handler)($record, $this);
            if (!$handler instanceof HandlerInterface) {
                throw new \RuntimeException("The factory Closure should return a HandlerInterface");
            }
            $this->handler = $handler;
        }

        return $this->handler;
    }

    /**
     * @inheritDoc
     */
    public function setFormatter(FormatterInterface $formatter): HandlerInterface
    {
        $handler = $this->getHandler();
        if ($handler instanceof FormattableHandlerInterface) {
            $handler->setFormatter($formatter);

            return $this;
        }

        throw new \UnexpectedValueException('The nested handler of type '.\get_class($handler).' does not support formatters.');
    }

    /**
     * @inheritDoc
     */
    public function getFormatter(): FormatterInterface
    {
        $handler = $this->getHandler();
        if ($handler instanceof FormattableHandlerInterface) {
            return $handler->getFormatter();
        }

        throw new \UnexpectedValueException('The nested handler of type '.\get_class($handler).' does not support formatters.');
    }
}
