<?php declare(strict_types=1);

namespace LaraGram\Log\Logger\Handler;

use LaraGram\Log\Logger\Level;
use LaraGram\Log\Logger\Logger;
use LaraGram\Log\Logger\ResettableInterface;
use LaraGram\Log\LogLevel;
use LaraGram\Log\Logger\LogRecord;

abstract class AbstractHandler extends Handler implements ResettableInterface
{
    protected Level $level = Level::Debug;
    protected bool $bubble = true;

    /**
     * @param int|string|Level|LogLevel::* $level  The minimum logging level at which this handler will be triggered
     * @param bool                         $bubble Whether the messages that are handled can bubble up the stack or not
     *
     * @phpstan-param value-of<Level::VALUES>|value-of<Level::NAMES>|Level|LogLevel::* $level
     */
    public function __construct(int|string|Level $level = Level::Debug, bool $bubble = true)
    {
        $this->setLevel($level);
        $this->bubble = $bubble;
    }

    /**
     * @inheritDoc
     */
    public function isHandling(LogRecord $record): bool
    {
        return $record->level->value >= $this->level->value;
    }

    /**
     * Sets minimum logging level at which this handler will be triggered.
     *
     * @param  Level|LogLevel::* $level Level or level name
     * @return $this
     *
     * @phpstan-param value-of<Level::VALUES>|value-of<Level::NAMES>|Level|LogLevel::* $level
     */
    public function setLevel(int|string|Level $level): self
    {
        $this->level = Logger::toLoggerLevel($level);

        return $this;
    }

    /**
     * Gets minimum logging level at which this handler will be triggered.
     */
    public function getLevel(): Level
    {
        return $this->level;
    }

    /**
     * Sets the bubbling behavior.
     *
     * @param  bool  $bubble true means that this handler allows bubbling.
     *                       false means that bubbling is not permitted.
     * @return $this
     */
    public function setBubble(bool $bubble): self
    {
        $this->bubble = $bubble;

        return $this;
    }

    /**
     * Gets the bubbling behavior.
     *
     * @return bool true means that this handler allows bubbling.
     *              false means that bubbling is not permitted.
     */
    public function getBubble(): bool
    {
        return $this->bubble;
    }

    /**
     * @inheritDoc
     */
    public function reset(): void
    {
    }
}
