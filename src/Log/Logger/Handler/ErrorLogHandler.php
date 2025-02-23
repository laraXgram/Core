<?php declare(strict_types=1);

namespace LaraGram\Log\Logger\Handler;

use LaraGram\Log\Logger\Formatter\LineFormatter;
use LaraGram\Log\Logger\Formatter\FormatterInterface;
use LaraGram\Log\Logger\Level;
use LaraGram\Log\Logger\Utils;
use LaraGram\Log\Logger\LogRecord;

class ErrorLogHandler extends AbstractProcessingHandler
{
    public const OPERATING_SYSTEM = 0;
    public const SAPI = 4;

    /** @var 0|1|3|4 */
    protected int $messageType;
    protected bool $expandNewlines;

    /**
     * @param 0|1|3|4 $messageType    Says where the error should go.
     * @param bool $expandNewlines If set to true, newlines in the message will be expanded to be take multiple log entries
     *
     * @throws \InvalidArgumentException If an unsupported message type is set
     */
    public function __construct(int $messageType = self::OPERATING_SYSTEM, int|string|Level $level = Level::Debug, bool $bubble = true, bool $expandNewlines = false)
    {
        parent::__construct($level, $bubble);

        if (false === \in_array($messageType, self::getAvailableTypes(), true)) {
            $message = sprintf('The given message type "%s" is not supported', print_r($messageType, true));

            throw new \InvalidArgumentException($message);
        }

        $this->messageType = $messageType;
        $this->expandNewlines = $expandNewlines;
    }

    /**
     * @return int[] With all available types
     */
    public static function getAvailableTypes(): array
    {
        return [
            self::OPERATING_SYSTEM,
            self::SAPI,
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getDefaultFormatter(): FormatterInterface
    {
        return new LineFormatter('[%datetime%] %channel%.%level_name%: %message% %context% %extra%');
    }

    /**
     * @inheritDoc
     */
    protected function write(LogRecord $record): void
    {
        if (!$this->expandNewlines) {
            error_log((string) $record->formatted, $this->messageType);

            return;
        }

        $lines = preg_split('{[\r\n]+}', (string) $record->formatted);
        if ($lines === false) {
            $pcreErrorCode = preg_last_error();

            throw new \RuntimeException('Failed to preg_split formatted string: ' . $pcreErrorCode . ' / '. preg_last_error_msg());
        }
        foreach ($lines as $line) {
            error_log($line, $this->messageType);
        }
    }
}
