<?php declare(strict_types=1);

namespace LaraGram\Log\Logger\Handler;

use LaraGram\Log\Logger\Level;
use LaraGram\Log\LogLevel;
use LaraGram\Log\Logger\Logger;
use LaraGram\Log\Logger\LogRecord;

class NullHandler extends Handler
{
    private Level $level;

    /**
     * @param string|int|Level $level The minimum logging level at which this handler will be triggered
     *
     * @phpstan-param value-of<Level::VALUES>|value-of<Level::NAMES>|Level|LogLevel::* $level
     */
    public function __construct(string|int|Level $level = Level::Debug)
    {
        $this->level = Logger::toLoggerLevel($level);
    }

    /**
     * @inheritDoc
     */
    public function isHandling(LogRecord $record): bool
    {
        return $record->level->value >= $this->level->value;
    }

    /**
     * @inheritDoc
     */
    public function handle(LogRecord $record): bool
    {
        return $record->level->value >= $this->level->value;
    }
}
