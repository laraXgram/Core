<?php

namespace LaraGram\Http\Client\Core;

use LaraGram\Http\Factory\MessageInterface;

interface BodySummarizerInterface
{
    /**
     * Returns a summarized message body.
     */
    public function summarize(MessageInterface $message): ?string;
}
