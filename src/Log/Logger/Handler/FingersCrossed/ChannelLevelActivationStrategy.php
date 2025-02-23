<?php declare(strict_types=1);

namespace LaraGram\Log\Logger\Handler\FingersCrossed;

use LaraGram\Log\Logger\Level;
use LaraGram\Log\Logger\Logger;
use LaraGram\Log\LogLevel;
use LaraGram\Log\Logger\LogRecord;

class ChannelLevelActivationStrategy implements ActivationStrategyInterface
{
    private Level $defaultActionLevel;

    /**
     * @var array<string, Level>
     */
    private array $channelToActionLevel;

    /**
     * @param int|string|Level|LogLevel::*                $defaultActionLevel   The default action level to be used if the record's category doesn't match any
     * @param array<string, int|string|Level|LogLevel::*> $channelToActionLevel An array that maps channel names to action levels.
     *
     * @phpstan-param value-of<Level::VALUES>|value-of<Level::NAMES>|Level|LogLevel::* $defaultActionLevel
     * @phpstan-param array<string, value-of<Level::VALUES>|value-of<Level::NAMES>|Level|LogLevel::*> $channelToActionLevel
     */
    public function __construct(int|string|Level $defaultActionLevel, array $channelToActionLevel = [])
    {
        $this->defaultActionLevel = Logger::toLoggerLevel($defaultActionLevel);
        $this->channelToActionLevel = array_map(Logger::toLoggerLevel(...), $channelToActionLevel);
    }

    public function isHandlerActivated(LogRecord $record): bool
    {
        if (isset($this->channelToActionLevel[$record->channel])) {
            return $record->level->value >= $this->channelToActionLevel[$record->channel]->value;
        }

        return $record->level->value >= $this->defaultActionLevel->value;
    }
}
