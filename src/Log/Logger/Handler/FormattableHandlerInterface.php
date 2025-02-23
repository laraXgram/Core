<?php declare(strict_types=1);

namespace LaraGram\Log\Logger\Handler;

use LaraGram\Log\Logger\Formatter\FormatterInterface;

interface FormattableHandlerInterface
{
    /**
     * Sets the formatter.
     *
     * @return HandlerInterface self
     */
    public function setFormatter(FormatterInterface $formatter): HandlerInterface;

    /**
     * Gets the formatter.
     */
    public function getFormatter(): FormatterInterface;
}
