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
     * Get the maximum invalid attempts allowed per question.
     *
     * @return int
     */
    public function maxAttempts(): int
    {
        return (int) ($this->maxAttempts ?? config('conversation.max_attempts', 3));
    }

    /**
     * Get the inactivity timeout in seconds (null disables it).
     *
     * @return int|null
     */
    public function cancelTimeout(): ?int
    {
        $timeout = $this->cancelTimeout ?? config('conversation.cancel_timeout');

        return $timeout === null ? null : (int) $timeout;
    }

    /**
     * Get the command/text that cancels the conversation (null disables it).
     *
     * @return string|null
     */
    public function cancelCommand(): ?string
    {
        return $this->cancelCommand ?? config('conversation.cancel_command');
    }

    /**
     * Determine whether answers are forgotten once the conversation completes.
     *
     * @return bool
     */
    public function forgetAfterComplete(): bool
    {
        return (bool) ($this->forgotAfterComplete ?? config('conversation.forget_after_complete', true));
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
    public function onAnswer(Request $request, Question $question, Answer $answer): void
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
     * @param  \LaraGram\Conversation\AnswersBag  $answers
     */
    public function onComplete(Request $request, AnswersBag $answers): void
    {
    }
}
