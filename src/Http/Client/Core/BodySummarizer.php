<?php

namespace LaraGram\Http\Client\Core;

use LaraGram\Http\Factory\MessageInterface;

final class BodySummarizer implements BodySummarizerInterface
{
    /**
     * @var int|null
     */
    private $truncateAt;

    public function __construct(?int $truncateAt = null)
    {
        $this->truncateAt = $truncateAt;
    }

    /**
     * Returns a summarized message body.
     */
    public function summarize(MessageInterface $message): ?string
    {
        return $this->truncateAt === null
            ? Message::bodySummary($message)
            : Message::bodySummary($message, $this->truncateAt);
    }
}
