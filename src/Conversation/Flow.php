<?php

namespace LaraGram\Conversation;

final class Flow
{
    /**
     * @param  string  $action  goto | repeat | finish | cancel
     * @param  string|null  $target  The question name for a jump.
     * @param  string|null  $reason  The cancellation reason.
     */
    private function __construct(
        public readonly string $action,
        public readonly ?string $target = null,
        public readonly ?string $reason = null,
    ) {
    }

    /**
     * Continue with the question named, instead of the next one.
     *
     * @param  string  $question
     * @return self
     */
    public static function goTo(string $question): self
    {
        return new self('goto', $question);
    }

    /**
     * Ask the current question again.
     *
     * @return self
     */
    public static function repeat(): self
    {
        return new self('repeat');
    }

    /**
     * Complete the conversation now, keeping the answers collected so far.
     *
     * @return self
     */
    public static function finish(): self
    {
        return new self('finish');
    }

    /**
     * Cancel the conversation now.
     *
     * @param  string  $reason
     * @return self
     */
    public static function cancel(string $reason = 'manual'): self
    {
        return new self('cancel', reason: $reason);
    }
}
