<?php declare(strict_types=1);

namespace LaraGram\Log\Logger\Handler\FingersCrossed;

use LaraGram\Log\Logger\Level;
use LaraGram\Log\Logger\LogRecord;
use LaraGram\Log\Logger\Logger;
use LaraGram\Log\LogLevel;

class ErrorLevelActivationStrategy implements ActivationStrategyInterface
{
    private Level $actionLevel;

    /**
     * @param int|string|Level $actionLevel Level or name or value
     *
     * @phpstan-param value-of<Level::VALUES>|value-of<Level::NAMES>|Level|LogLevel::* $actionLevel
     */
    public function __construct(int|string|Level $actionLevel)
    {
        $this->actionLevel = Logger::toLoggerLevel($actionLevel);
    }

    public function isHandlerActivated(LogRecord $record): bool
    {
        return $record->level->value >= $this->actionLevel->value;
    }
}
