<?php declare(strict_types=1);

namespace LaraGram\Log\Logger\Processor;

use LaraGram\Log\Logger\LogRecord;

interface ProcessorInterface
{
    /**
     * @return LogRecord The processed record
     */
    public function __invoke(LogRecord $record);
}
