<?php

namespace LaraGram\Conversation;

use BadMethodCallException;
use Closure;
use LaraGram\Support\SerializableClosure\SerializableClosure;

class InlineConversationBuilder
{
    /**
     * The lifecycle hook closures keyed by hook name.
     *
     * @var array<string, \Closure>
     */
    protected array $hooks = [];

    /**
     * The conversation settings (maxAttempts, cancelTimeout, ...).
     *
     * @var array<string, mixed>
     */
    protected array $settings = [];

    /**
     * Parameters passed to the conversation on start.
     *
     * @var array<string, mixed>
     */
    protected array $parameters = [];

    /**
     * Whether the conversation has already been started.
     *
     * @var bool
     */
    protected bool $started = false;

    /**
     * The single question declared with Conversation::ask(), if any.
     *
     * @var array{prompt: string|\Closure, name: string}|null
     */
    protected ?array $single = null;

    /**
     * The builder calls forwarded to that single question.
     *
     * @var array<int, array{0: string, 1: array}>
     */
    protected array $questionCalls = [];

    /**
     * @param  \LaraGram\Conversation\ConversationManager  $manager
     * @param  \Closure|null  $builder
     */
    public function __construct(
        protected ConversationManager $manager,
        protected ?Closure $builder = null,
    ) {
    }

    /**
     * Declare the single question of a one-question conversation.
     *
     * Every method the question understands may then be called on the builder
     * itself: validate(), choices(), template(), optional(), and so on.
     *
     * @param  string|\Closure  $prompt
     * @param  string  $name
     * @return $this
     */
    public function question(string|Closure $prompt, string $name = 'answer'): static
    {
        $this->single = ['prompt' => $prompt, 'name' => $name];

        return $this;
    }

    /**
     * Forward an unknown method to the single question being built.
     *
     * @param  string  $method
     * @param  array  $arguments
     * @return $this
     *
     * @throws \BadMethodCallException
     */
    public function __call(string $method, array $arguments): static
    {
        if ($this->single === null) {
            throw new BadMethodCallException(
                "Method [{$method}] is only available on a conversation started with Conversation::ask()."
            );
        }

        $this->questionCalls[] = [$method, array_map(
            static fn ($argument) => $argument instanceof Closure ? new SerializableClosure($argument) : $argument,
            $arguments
        )];

        return $this;
    }

    public function onStart(Closure $callback): static
    {
        return $this->hook('onStart', $callback);
    }

    public function onAsk(Closure $callback): static
    {
        return $this->hook('onAsk', $callback);
    }

    public function onAnswer(Closure $callback): static
    {
        return $this->hook('onAnswer', $callback);
    }

    public function onSkip(Closure $callback): static
    {
        return $this->hook('onSkip', $callback);
    }

    public function onBack(Closure $callback): static
    {
        return $this->hook('onBack', $callback);
    }

    /**
     * Configure the conversation-wide back control.
     *
     * @param  string|null  $mode          reply | inline | command | text | none
     * @param  string|null  $label         Button text / matched text.
     * @param  string|null  $callbackData  Callback data for inline mode.
     * @param  string|null  $command       Command that triggers back.
     * @return $this
     */
    public function back(
        ?string $mode = null,
        ?string $label = null,
        ?string $callbackData = null,
        ?string $command = null,
    ): static {
        $this->settings['back'] = Back::make($mode, $label, $callbackData, $command, enabled: true);

        return $this;
    }

    /**
     * Disable the back control for the whole conversation.
     *
     * @return $this
     */
    public function noBack(): static
    {
        $this->settings['back'] = Back::disabled();

        return $this;
    }

    /**
     * Set the conversation-wide priority (listens-first by default, or make the
     * conversation handle updates before listens).
     *
     * @param  \LaraGram\Conversation\Priority  $priority
     * @return $this
     */
    public function priority(Priority $priority): static
    {
        $this->settings['priority'] = $priority;

        return $this;
    }

    public function onInvalid(Closure $callback): static
    {
        return $this->hook('onInvalid', $callback);
    }

    public function onCancel(Closure $callback): static
    {
        return $this->hook('onCancel', $callback);
    }

    public function onComplete(Closure $callback): static
    {
        return $this->hook('onComplete', $callback);
    }

    public function maxAttempts(int $attempts): static
    {
        $this->settings['maxAttempts'] = $attempts;

        return $this;
    }

    public function cancelTimeout(?int $seconds): static
    {
        $this->settings['cancelTimeout'] = $seconds;

        return $this;
    }

    public function cancelCommand(?string $command): static
    {
        $this->settings['cancelCommand'] = $command;

        return $this;
    }

    public function forgetAfterComplete(bool $forget = true): static
    {
        $this->settings['forgotAfterComplete'] = $forget;

        return $this;
    }

    /**
     * Set the message sent when an answer is rejected (false sends none).
     *
     * @param  string|bool|null  $message
     * @return $this
     */
    public function retryMessage(string|bool|null $message): static
    {
        $this->settings['retryMessage'] = $message;

        return $this;
    }

    /**
     * Decide what happens to the keyboard of the last prompt once the
     * conversation is over.
     *
     * @param  string|bool  $clear
     * @return $this
     */
    public function clearKeyboard(string|bool $clear = true): static
    {
        $this->settings['clearKeyboard'] = $clear;

        return $this;
    }

    /**
     * Label the conversation (used in events; defaults to "inline").
     */
    public function name(string $name): static
    {
        $this->settings['name'] = $name;

        return $this;
    }

    /**
     * Set parameters passed to the conversation.
     *
     * @param  array<string, mixed>  $parameters
     * @return $this
     */
    public function with(array $parameters): static
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * Start the conversation now (idempotent).
     *
     * @param  array<string, mixed>  $parameters
     * @return void
     */
    public function start(array $parameters = []): void
    {
        if ($this->started) {
            return;
        }

        $this->started = true;

        $this->manager->startInline($this->toPayload(), $parameters ?: $this->parameters);
    }

    /**
     * Auto-start when the builder is discarded without an explicit start().
     */
    public function __destruct()
    {
        if (! $this->started) {
            $this->start();
        }
    }

    /**
     * Register a hook closure.
     *
     * @param  string  $name
     * @param  \Closure  $callback
     * @return $this
     */
    protected function hook(string $name, Closure $callback): static
    {
        $this->hooks[$name] = $callback;

        return $this;
    }

    /**
     * Build the serializable payload persisted into the conversation state.
     *
     * @return array<string, mixed>
     */
    protected function toPayload(): array
    {
        $wrap = static fn (Closure $closure) => new SerializableClosure($closure);

        $hooks = [];

        foreach ($this->hooks as $name => $closure) {
            $hooks[$name] = $wrap($closure);
        }

        return [
            'name'     => $this->settings['name'] ?? 'inline',
            'builder'  => $wrap($this->resolveBuilder()),
            'hooks'    => $hooks,
            'settings' => $this->settings,
        ];
    }

    /**
     * Get the closure declaring the conversation's questions.
     *
     * @return \Closure
     */
    protected function resolveBuilder(): Closure
    {
        if ($this->builder !== null) {
            return $this->builder;
        }

        $single = $this->single;
        $calls = $this->questionCalls;

        return static function (Questioner $questioner) use ($single, $calls) {
            $question = $questioner->ask($single['prompt'])->name($single['name']);

            foreach ($calls as [$method, $arguments]) {
                $question->{$method}(...array_map(
                    static fn ($argument) => $argument instanceof SerializableClosure
                        ? $argument->getClosure()
                        : $argument,
                    $arguments
                ));
            }
        };
    }
}
