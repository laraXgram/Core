<?php

namespace LaraGram\Conversation;

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
     * @param  \LaraGram\Conversation\ConversationManager  $manager
     * @param  \Closure  $builder
     */
    public function __construct(
        protected ConversationManager $manager,
        protected Closure $builder,
    ) {
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
            'builder'  => $wrap($this->builder),
            'hooks'    => $hooks,
            'settings' => $this->settings,
        ];
    }
}
