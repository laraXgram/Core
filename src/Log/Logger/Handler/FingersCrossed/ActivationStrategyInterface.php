<?php declare(strict_types=1);


namespace LaraGram\Log\Logger\Handler\FingersCrossed;

use LaraGram\Log\Logger\LogRecord;

interface ActivationStrategyInterface
{
    /**
     * Returns whether the given record activates the handler.
     */
    public function isHandlerActivated(LogRecord $record): bool;
}
