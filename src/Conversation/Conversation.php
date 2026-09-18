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
     * Get the conversation-wide back control (applies to every question but the
     * first). Override this to return Back::make(...) / Back::disabled(), or
     * declare a public ?Back $back property.
     *
     * @return \LaraGram\Conversation\Back|null
     */
    public function back(): ?Back
    {
        return $this->back ?? null;
    }

    /**
     * Get the conversation-wide priority: whether regular/step listens handle an
     * update first (Priority::Listen, the default) or the conversation does
     * (Priority::Conversation). Override this or declare a public ?Priority
     * $priority property.
     *
     * @return \LaraGram\Conversation\Priority|null
     */
    public function priority(): ?Priority
    {
        return $this->priority ?? null;
    }

    /**
     * The parameters the conversation was started with.
     *
     * @var array<string, mixed>
     */
    protected array $parameters = [];

    /**
     * Set the parameters the conversation was started with.
     *
     * @param  array<string, mixed>  $parameters
     * @return $this
     */
    public function withParameters(array $parameters): static
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * Get the parameters the conversation was started with.
     *
     * @return array<string, mixed>
     */
    public function parameters(): array
    {
        return $this->parameters;
    }

    /**
     * Get one of the parameters the conversation was started with.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function parameter(string $key, mixed $default = null): mixed
    {
        return $this->parameters[$key] ?? $default;
    }

    /**
     * Get the message sent when an answer is rejected.
     *
     * A string is sent as it is, true sends the first validation error, and
     * false or null sends nothing. Override this or declare a $retryMessage
     * property; questions may override it again with retry().
     *
     * @return string|bool|null
     */
    public function retryMessage(): string|bool|null
    {
        return $this->retryMessage ?? config('conversation.retry_message', true);
    }

    /**
     * Determine what happens to the keyboard of the last prompt once the
     * conversation is over.
     *
     * True takes an inline keyboard back and, for a reply keyboard, sends the
     * configured message that removes it; a string is used as that message;
     * false leaves the keyboard alone.
     *
     * @return string|bool
     */
    public function clearKeyboard(): string|bool
    {
        return $this->clearKeyboard ?? config('conversation.clear_keyboard', true);
    }

    /**
     * Steer the conversation to another question from a hook or callback.
     *
     * @param  string  $question
     * @return \LaraGram\Conversation\Flow
     */
    protected function goTo(string $question): Flow
    {
        return Flow::goTo($question);
    }

    /**
     * Complete the conversation early from a hook or callback.
     *
     * @return \LaraGram\Conversation\Flow
     */
    protected function finish(): Flow
    {
        return Flow::finish();
    }

    /**
     * Ask the current question again from a hook or callback.
     *
     * @return \LaraGram\Conversation\Flow
     */
    protected function repeat(): Flow
    {
        return Flow::repeat();
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
     *
     * Return a {@see Flow} to steer the conversation from here.
     *
     * @return \LaraGram\Conversation\Flow|null
     */
    public function onAnswer(Request $request, Question $question, Answer $answer)
    {
    }

    /**
     * Called when a question is skipped via its skip command or skip button.
     */
    public function onSkip(Request $request, Question $question): void
    {
    }

    /**
     * Called when the user goes back to the previous question.
     *
     * $question is the previous question being re-asked. Return a {@see Flow}
     * to go somewhere else instead.
     *
     * @return \LaraGram\Conversation\Flow|null
     */
    public function onBack(Request $request, Question $question)
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
     * Reasons: "command", "timeout", "max_attempts", "interrupted", "manual".
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
