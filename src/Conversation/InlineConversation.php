<?php

namespace LaraGram\Conversation;

use Closure;
use LaraGram\Request\Request;
use LaraGram\Support\SerializableClosure\SerializableClosure;

/**
 * @internal Built by {@see InlineConversationBuilder};
 */
class InlineConversation extends Conversation
{
    /**
     * @param  \Closure  $builder
     * @param  array<string, \Closure>  $hooks
     * @param  array<string, mixed>  $settings
     */
    public function __construct(
        protected Closure $builder,
        protected array $hooks = [],
        protected array $settings = [],
    ) {
    }

    /**
     * Rebuild an inline conversation from a (possibly serialized) payload.
     *
     * @param  array  $payload
     * @return static
     */
    public static function fromPayload(array $payload): static
    {
        $unwrap = static fn ($closure) => $closure instanceof SerializableClosure
            ? $closure->getClosure()
            : $closure;

        $hooks = [];

        foreach (($payload['hooks'] ?? []) as $name => $closure) {
            if ($closure !== null) {
                $hooks[$name] = $unwrap($closure);
            }
        }

        return new static($unwrap($payload['builder']), $hooks, $payload['settings'] ?? []);
    }

    /**
     * Declare the conversation's questions via the builder closure.
     *
     * @return void
     */
    public function start(): void
    {
        ($this->builder)($this->questioner());
    }

    /**
     * @return int
     */
    public function maxAttempts(): int
    {
        return (int) ($this->settings['maxAttempts'] ?? parent::maxAttempts());
    }

    /**
     * @return int|null
     */
    public function cancelTimeout(): ?int
    {
        if (! array_key_exists('cancelTimeout', $this->settings)) {
            return parent::cancelTimeout();
        }

        $timeout = $this->settings['cancelTimeout'];

        return $timeout === null ? null : (int) $timeout;
    }

    /**
     * @return string|null
     */
    public function cancelCommand(): ?string
    {
        return array_key_exists('cancelCommand', $this->settings)
            ? $this->settings['cancelCommand']
            : parent::cancelCommand();
    }

    /**
     * @return bool
     */
    public function forgetAfterComplete(): bool
    {
        return array_key_exists('forgotAfterComplete', $this->settings)
            ? (bool) $this->settings['forgotAfterComplete']
            : parent::forgetAfterComplete();
    }

    /**
     * @return \LaraGram\Conversation\Back|null
     */
    public function back(): ?Back
    {
        return $this->settings['back'] ?? null;
    }

    /**
     * @return \LaraGram\Conversation\Priority|null
     */
    public function priority(): ?Priority
    {
        return $this->settings['priority'] ?? null;
    }

    public function onStart(Request $request): void
    {
        $this->fire('onStart', $request);
    }

    public function onBack(Request $request, Question $question): void
    {
        $this->fire('onBack', $request, $question);
    }

    public function onAsk(Request $request, Question $question): void
    {
        $this->fire('onAsk', $request, $question);
    }

    public function onAnswer(Request $request, Question $question, Answer $answer): void
    {
        $this->fire('onAnswer', $request, $question, $answer);
    }

    public function onSkip(Request $request, Question $question): void
    {
        $this->fire('onSkip', $request, $question);
    }

    public function onInvalid(Request $request, Question $question, array $errors, int $attempt): void
    {
        $this->fire('onInvalid', $request, $question, $errors, $attempt);
    }

    public function onCancel(Request $request, string $reason): void
    {
        $this->fire('onCancel', $request, $reason);
    }

    public function onComplete(Request $request, AnswersBag $answers): void
    {
        $this->fire('onComplete', $request, $answers);
    }

    /**
     * Invoke a hook closure if one was registered.
     *
     * @param  string  $hook
     * @param  mixed  ...$arguments
     * @return void
     */
    protected function fire(string $hook, ...$arguments): void
    {
        if (isset($this->hooks[$hook])) {
            ($this->hooks[$hook])(...$arguments);
        }
    }
}
