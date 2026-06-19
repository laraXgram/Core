<?php

namespace LaraGram\Conversation;

use Closure;
use LaraGram\Request\Request;

/**
 * Base class for all conversations.
 */
abstract class Conversation
{
    /**
     * The maximum number of invalid attempts allowed per question before the
     * conversation is cancelled.
     *
     * @var int
     */
    public $maxAttempts = 3;

    /**
     * The number of seconds of inactivity after which the conversation is
     * cancelled. Null disables the timeout.
     *
     * @var int|null
     */
    public $cancelTimeout = null;

    /**
     * The command/text that cancels the conversation at any point. Null
     * disables command cancellation.
     *
     * @var string|null
     */
    public $cancelCommand = null;

    /**
     * Whether collected answers are forgotten once the conversation completes.
     *
     * @var bool
     */
    public $forgotAfterComplete = true;

    /**
     * The questioner collecting this conversation's questions.
     *
     * @var \LaraGram\Conversation\Questioner|null
     */
    protected ?Questioner $questioner = null;

    /**
     * Declare the conversation's questions.
     *
     * @return void
     */
    abstract public function start(): void;

    /**
     * Declare questions through a callback receiving the Questioner.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public function create(Closure $callback): void
    {
        $callback($this->questioner());
    }

    /**
     * Get the questioner, building the question list on first access.
     *
     * @return \LaraGram\Conversation\Questioner
     */
    public function questioner(): Questioner
    {
        return $this->questioner ??= new Questioner;
    }

    /**
     * Build (or rebuild) the question list and return it.
     *
     * @return \LaraGram\Conversation\Questioner
     */
    public function build(): Questioner
    {
        if ($this->questioner()->isEmpty()) {
            $this->start();
        }

        return $this->questioner();
    }

    /**
     * Get the configured maximum attempts per question.
     *
     * @return int
     */
    public function maxAttempts(): int
    {
        return $this->maxAttempts;
    }

    /**
     * Called once when the conversation begins.
     */
    public function onStart(Request $request): void
    {
    }

    /**
     * Called right before a question is sent to the user.
     */
    public function onAsk(Request $request, Question $question): void
    {
    }

    /**
     * Called when a question receives a valid answer.
     */
    public function onAnswer(Request $request, Question $question, mixed $answer): void
    {
    }

    /**
     * Called when a question is skipped via its skip command.
     */
    public function onSkip(Request $request, Question $question): void
    {
    }

    /**
     * Called when an answer fails validation (before the next attempt).
     */
    public function onInvalid(Request $request, Question $question, array $errors, int $attempt): void
    {
    }

    /**
     * Called when the conversation is cancelled.
     *
     * Reasons: "command", "timeout", "max_attempts", "manual".
     */
    public function onCancel(Request $request, string $reason): void
    {
    }

    /**
     * Called when all questions have been answered.
     *
     * @param  array<string, mixed>  $answers
     */
    public function onComplete(Request $request, array $answers): void
    {
    }
}
