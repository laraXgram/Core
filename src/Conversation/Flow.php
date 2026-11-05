<?php

namespace LaraGram\Conversation;

final class Flow
{
    /**
     * @param  string  $action  goto | repeat | finish | cancel | navigate
     * @param  string|null  $target  The question name for a jump.
     * @param  string|null  $reason  The cancellation reason.
     * @param  int|string  $step  The number of steps, or the name of a step, to go back to.
     */
    private function __construct(
        public readonly string $action,
        public readonly ?string $target = null,
        public readonly ?string $reason = null,
        public readonly int|string $step = 1,
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

    /**
     * Cancel the conversation and take the user back along their navigation trail.
     *
     * @param  int|string  $step  The number of steps, or the name of a step.
     * @return self
     */
    public static function back(int|string $step = 1): self
    {
        return new self('navigate', reason: 'back', step: $step);
    }
}
