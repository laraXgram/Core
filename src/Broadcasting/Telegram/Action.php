<?php

namespace LaraGram\Broadcasting\Telegram;

use LaraGram\Broadcasting\BroadcastException;
use LaraGram\Contracts\Support\Arrayable;
use LaraGram\Container\Container;
use LaraGram\Request\Request;
use LaraGram\Template\Template;
use ReflectionMethod;

class Action implements Arrayable
{
    /**
     * The method name used by template actions.
     *
     * @var string
     */
    public const TEMPLATE = '@template';

    /**
     * Create a new broadcast action.
     *
     * @param  string  $method  The Bot API method name, or Action::TEMPLATE.
     * @param  array<string, mixed>  $parameters  The parameters, without the recipient.
     * @param  array<string, mixed>  $options  Delivery options (see TelegramBroadcast).
     * @param  string|null  $target  The parameter receiving the recipient (chat_id by default).
     * @param  array{source: string, template: string, data: array, per_recipient: bool}|null  $template
     *
     * @throws \LaraGram\Broadcasting\BroadcastException
     */
    public function __construct(
        public string $method,
        public array $parameters = [],
        public array $options = [],
        public ?string $target = null,
        public ?array $template = null,
    ) {
        if (! static::isMethodName($method)) {
            throw new BroadcastException(
                "Telegram broadcasts need a Bot API method name, [{$method}] given. Return one from broadcastAs() or use an Action."
            );
        }

        if ($method === static::TEMPLATE && empty($template['template'])) {
            throw new BroadcastException('A template broadcast action needs a template name.');
        }
    }

    /**
     * Create a new broadcast action.
     *
     * @param  string  $method
     * @param  array<string, mixed>  $parameters
     * @return static
     */
    public static function make(string $method, array $parameters = [])
    {
        return new static($method, $parameters);
    }

    /**
     * Create an action that renders a template for every recipient.
     *
     * @param  \LaraGram\Template\Template|string  $template
     * @param  array  $data
     * @param  bool  $perRecipient  Render once per recipient (true) or once per chunk (false).
     * @return static
     *
     * @throws \LaraGram\Broadcasting\BroadcastException
     */
    public static function template(Template|string $template, array $data = [], bool $perRecipient = true)
    {
        if ($template instanceof Template) {
            [$data, $template] = [array_merge($template->getData(), $data), $template->getName()];
        }

        $factory = Container::getInstance()->make(\LaraGram\Contracts\Template\Factory::class);

        try {
            $exists = $factory->exists($template);
        } catch (\Throwable) {
            $exists = false;
        }

        $source = match (true) {
            $exists => 'name',
            is_file($template) => 'path',
            (bool) preg_match('/[@<\n]|\{\{|\{!!/', $template) => 'inline',
            default => throw new BroadcastException("Template [{$template}] was not found."),
        };

        return new static(static::TEMPLATE, template: [
            'source' => $source,
            'template' => $template,
            'data' => $data,
            'per_recipient' => $perRecipient,
        ]);
    }

    /**
     * Create the action of a generated Bot API method call.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @param  string  $recipient  The parameter receiving each recipient.
     * @return static
     */
    public static function fromEndpoint(string $method, array $parameters, string $recipient)
    {
        return new static(
            $method,
            array_filter($parameters, fn ($value) => $value !== null),
            target: $recipient === 'chat_id' ? null : $recipient,
        );
    }

    /**
     * Create the action of a Bot API method reached through __call.
     *
     * @param  string  $class
     * @param  string  $method
     * @param  array  $arguments
     * @return static
     *
     * @throws \BadMethodCallException
     */
    public static function fromCall(string $class, string $method, array $arguments)
    {
        if (static::parameterNames($method) === null) {
            throw new \BadMethodCallException(sprintf('Method %s::%s does not exist.', $class, $method));
        }

        return new static($method, static::mapArguments($method, $arguments), target: static::recipientParameter($method));
    }

    /**
     * Add the action as a step of the broadcast.
     *
     * @param  \LaraGram\Broadcasting\Telegram\TelegramBroadcast  $broadcast
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function appendTo(TelegramBroadcast $broadcast): TelegramBroadcast
    {
        $action = $this;

        return (fn () => $this->addStep($action))->call($broadcast);
    }

    /**
     * Get the parameter that receives the recipient of a Bot API method.
     *
     * @param  string  $method
     * @return string|null
     */
    public static function recipientParameter(string $method): ?string
    {
        $names = static::parameterNames($method);

        return $names !== null && ! in_array('chat_id', $names, true) && in_array('user_id', $names, true)
            ? 'user_id'
            : null;
    }

    /**
     * Get the parameter names of a Bot API method known to Laraquest.
     *
     * @param  string  $method
     * @return array<int, string>|null
     */
    public static function parameterNames(string $method): ?array
    {
        static $names = [];

        if (! array_key_exists($method, $names)) {
            $names[$method] = method_exists(Request::class, $method) && (new ReflectionMethod(Request::class, $method))->isPublic()
                ? array_map(fn ($parameter) => $parameter->getName(), (new ReflectionMethod(Request::class, $method))->getParameters())
                : null;
        }

        return $names[$method];
    }

    /**
     * Rebuild the steps and options carried by a broadcast event name and payload.
     *
     * @param  string  $event
     * @param  array  $payload
     * @return array{0: array<int, static>, 1: array}
     */
    public static function parse(string $event, array $payload): array
    {
        unset($payload['socket']);

        if (isset($payload['steps']) && is_array($payload['steps'])) {
            return [
                array_map(fn (array $step) => static::fromArray($step), array_values($payload['steps'])),
                (array) ($payload['options'] ?? []),
            ];
        }

        if (isset($payload['method']) && is_string($payload['method'])) {
            $action = static::fromArray($payload);

            return [[$action], $action->options];
        }

        $options = (array) ($payload['broadcast'] ?? []);

        unset($payload['broadcast']);

        return [[new static($event, $payload)], $options];
    }

    /**
     * Create an action from its array form.
     *
     * @param  array  $action
     * @return static
     */
    public static function fromArray(array $action)
    {
        return new static(
            $action['method'],
            (array) ($action['parameters'] ?? []),
            (array) ($action['options'] ?? []),
            $action['target'] ?? null,
            $action['template'] ?? null,
        );
    }

    /**
     * Merge parameters into the call.
     *
     * @param  array<string, mixed>  $parameters
     * @return $this
     */
    public function with(array $parameters)
    {
        $this->parameters = array_merge($this->parameters, $parameters);

        return $this;
    }

    /**
     * Merge delivery options into the action.
     *
     * @param  array<string, mixed>  $options
     * @return $this
     */
    public function options(array $options)
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * Name the parameter that receives the recipient.
     *
     * @param  string  $parameter
     * @return $this
     */
    public function target(string $parameter)
    {
        $this->target = $parameter;

        return $this;
    }

    /**
     * Determine if the action renders a template.
     *
     * @return bool
     */
    public function isTemplate(): bool
    {
        return $this->method === static::TEMPLATE;
    }

    /**
     * Get the parameters for a single recipient.
     *
     * @param  int|string  $chatId
     * @param  string  $target  The default target when the action names none.
     * @return array<string, mixed>
     */
    public function parametersFor(int|string $chatId, string $target = 'chat_id'): array
    {
        return array_merge($this->parameters, [$this->target ?? $target => $chatId]);
    }

    /**
     * Get the array form of the action.
     *
     * @return array{method: string, parameters: array, options: array, target: string|null, template: array|null}
     */
    public function toArray(): array
    {
        return [
            'method' => $this->method,
            'parameters' => $this->parameters,
            'options' => $this->options,
            'target' => $this->target,
            'template' => $this->template,
        ];
    }

    /**
     * Determine if the given string looks like a Bot API method name.
     *
     * @param  string  $method
     * @return bool
     */
    public static function isMethodName(string $method): bool
    {
        return $method === static::TEMPLATE || (bool) preg_match('/^[a-z][A-Za-z0-9]*$/', $method);
    }

    /**
     * Map call arguments to named Bot API parameters.
     *
     * @param  string  $method
     * @param  array  $arguments
     * @return array<string, mixed>
     *
     * @throws \LaraGram\Broadcasting\BroadcastException
     */
    public static function mapArguments(string $method, array $arguments): array
    {
        $named = array_filter($arguments, 'is_string', ARRAY_FILTER_USE_KEY);
        $positional = array_values(array_filter($arguments, 'is_int', ARRAY_FILTER_USE_KEY));

        if ($positional === []) {
            return static::withoutNulls($named);
        }

        if (is_null($all = static::parameterNames($method))) {
            throw new BroadcastException("Pass named arguments to the [{$method}] broadcast action.");
        }

        $names = array_values($all);

        $mapped = [];

        foreach ($positional as $index => $value) {
            if (! isset($names[$index])) {
                throw new BroadcastException("Too many arguments passed to the [{$method}] broadcast action.");
            }

            $mapped[$names[$index]] = $value;
        }

        return static::withoutNulls(array_merge($mapped, $named));
    }

    /**
     * Remove the parameters that were not given.
     *
     * @param  array  $parameters
     * @return array
     */
    protected static function withoutNulls(array $parameters): array
    {
        return array_filter($parameters, fn ($value) => $value !== null);
    }
}
