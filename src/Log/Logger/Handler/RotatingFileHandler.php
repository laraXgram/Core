<?php declare(strict_types=1);

namespace LaraGram\Log\Logger\Handler;

use InvalidArgumentException;
use LaraGram\Log\Logger\Level;
use LaraGram\Log\Logger\Utils;
use LaraGram\Log\Logger\LogRecord;

class RotatingFileHandler extends StreamHandler
{
    public const FILE_PER_DAY = 'Y-m-d';
    public const FILE_PER_MONTH = 'Y-m';
    public const FILE_PER_YEAR = 'Y';

    protected string $filename;
    protected int $maxFiles;
    protected bool|null $mustRotate = null;
    protected \DateTimeImmutable $nextRotation;
    protected string $filenameFormat;
    protected string $dateFormat;

    /**
     * @param int      $maxFiles       The maximal amount of files to keep (0 means unlimited)
     * @param int|null $filePermission Optional file permissions (default (0644) are only for owner read/write)
     * @param bool     $useLocking     Try to lock log file before doing any writes
     */
    public function __construct(string $filename, int $maxFiles = 0, int|string|Level $level = Level::Debug, bool $bubble = true, ?int $filePermission = null, bool $useLocking = false, string $dateFormat = self::FILE_PER_DAY, string $filenameFormat  = '{filename}-{date}')
    {
        $this->filename = Utils::canonicalizePath($filename);
        $this->maxFiles = $maxFiles;
        $this->setFilenameFormat($filenameFormat, $dateFormat);
        $this->nextRotation = $this->getNextRotation();

        parent::__construct($this->getTimedFilename(), $level, $bubble, $filePermission, $useLocking);
    }

    /**
     * @inheritDoc
     */
    public function close(): void
    {
        parent::close();

        if (true === $this->mustRotate) {
            $this->rotate();
        }
    }

    /**
     * @inheritDoc
     */
    public function reset(): void
    {
        parent::reset();
    }

    /**
     * @return $this
     */
    public function setFilenameFormat(string $filenameFormat, string $dateFormat): self
    {
        $this->setDateFormat($dateFormat);
        if (substr_count($filenameFormat, '{date}') === 0) {
            throw new InvalidArgumentException(
                'Invalid filename format - format must contain at least `{date}`, because otherwise rotating is impossible.'
            );
        }
        $this->filenameFormat = $filenameFormat;
        $this->url = $this->getTimedFilename();
        $this->close();

        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function write(LogRecord $record): void
    {
        // on the first record written, if the log is new, we rotate (once per day) after the log has been written so that the new file exists
        if (null === $this->mustRotate) {
            $this->mustRotate = null === $this->url || !file_exists($this->url);
        }

        // if the next rotation is expired, then we rotate immediately
        if ($this->nextRotation <= $record->datetime) {
            $this->mustRotate = true;
            $this->close(); // triggers rotation
        }

        parent::write($record);

        if (true === $this->mustRotate) {
            $this->close(); // triggers rotation
        }
    }

    /**
     * Rotates the files.
     */
    protected function rotate(): void
    {
        // update filename
        $this->url = $this->getTimedFilename();
        $this->nextRotation = $this->getNextRotation();

        $this->mustRotate = false;

        // skip GC of old logs if files are unlimited
        if (0 === $this->maxFiles) {
            return;
        }

        $logFiles = glob($this->getGlobPattern());
        if (false === $logFiles) {
            // failed to glob
            return;
        }

        if ($this->maxFiles >= \count($logFiles)) {
            // no files to remove
            return;
        }

        // Sorting the files by name to remove the older ones
        usort($logFiles, function ($a, $b) {
            return strcmp($b, $a);
        });

        foreach (\array_slice($logFiles, $this->maxFiles) as $file) {
            if (is_writable($file)) {
                // suppress errors here as unlink() might fail if two processes
                // are cleaning up/rotating at the same time
                set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
                    return false;
                });
                unlink($file);
                restore_error_handler();
            }
        }
    }

    protected function getTimedFilename(): string
    {
        $fileInfo = pathinfo($this->filename);
        $timedFilename = str_replace(
            ['{filename}', '{date}'],
            [$fileInfo['filename'], date($this->dateFormat)],
            ($fileInfo['dirname'] ?? '') . '/' . $this->filenameFormat
        );

        if (isset($fileInfo['extension'])) {
            $timedFilename .= '.'.$fileInfo['extension'];
        }

        return $timedFilename;
    }

    protected function getGlobPattern(): string
    {
        $fileInfo = pathinfo($this->filename);
        $glob = str_replace(
            ['{filename}', '{date}'],
            [$fileInfo['filename'], str_replace(
                ['Y', 'y', 'm', 'd'],
                ['[0-9][0-9][0-9][0-9]', '[0-9][0-9]', '[0-9][0-9]', '[0-9][0-9]'],
                $this->dateFormat
            )],
            ($fileInfo['dirname'] ?? '') . '/' . $this->filenameFormat
        );
        if (isset($fileInfo['extension'])) {
            $glob .= '.'.$fileInfo['extension'];
        }

        return $glob;
    }

    protected function setDateFormat(string $dateFormat): void
    {
        if (0 === preg_match('{^[Yy](([/_.-]?m)([/_.-]?d)?)?$}', $dateFormat)) {
            throw new InvalidArgumentException(
                'Invalid date format - format must be one of '.
                'RotatingFileHandler::FILE_PER_DAY ("Y-m-d"), RotatingFileHandler::FILE_PER_MONTH ("Y-m") '.
                'or RotatingFileHandler::FILE_PER_YEAR ("Y"), or you can set one of the '.
                'date formats using slashes, underscores and/or dots instead of dashes.'
            );
        }
        $this->dateFormat = $dateFormat;
    }

    protected function getNextRotation(): \DateTimeImmutable
    {
        return match (str_replace(['/','_','.'], '-', $this->dateFormat)) {
            self::FILE_PER_MONTH => (new \DateTimeImmutable('first day of next month'))->setTime(0, 0, 0),
            self::FILE_PER_YEAR => (new \DateTimeImmutable('first day of January next year'))->setTime(0, 0, 0),
            default => (new \DateTimeImmutable('tomorrow'))->setTime(0, 0, 0),
        };
    }
}
