<?php declare(strict_types=1);

namespace LaraGram\Log\Logger\Handler;

use LaraGram\Log\Logger\Level;
use LaraGram\Log\Logger\LogRecord;

class SyslogHandler extends AbstractSyslogHandler
{
    protected string $ident;
    protected int $logopts;

    /**
     * @param string|int $facility Either one of the names of the keys in $this->facilities, or a LOG_* facility constant
     * @param int        $logopts  Option flags for the openlog() call, defaults to LOG_PID
     */
    public function __construct(string $ident, string|int $facility = LOG_USER, int|string|Level $level = Level::Debug, bool $bubble = true, int $logopts = LOG_PID)
    {
        parent::__construct($facility, $level, $bubble);

        $this->ident = $ident;
        $this->logopts = $logopts;
    }

    /**
     * @inheritDoc
     */
    public function close(): void
    {
        closelog();
    }

    /**
     * @inheritDoc
     */
    protected function write(LogRecord $record): void
    {
        openlog($this->ident, $this->logopts, $this->facility);
        syslog($this->toSyslogPriority($record->level), (string) $record->formatted);
    }
}
