<?php declare(strict_types=1);

namespace LaraGram\Log\Logger\Formatter;

use LaraGram\Log\Logger\LogRecord;

interface FormatterInterface
{
    /**
     * Formats a log record.
     *
     * @param  LogRecord $record A record to format
     * @return mixed     The formatted record
     */
    public function format(LogRecord $record);

    /**
     * Formats a set of log records.
     *
     * @param  array<LogRecord> $records A set of records to format
     * @return mixed            The formatted set of records
     */
    public function formatBatch(array $records);
}
