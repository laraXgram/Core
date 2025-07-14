<?php

namespace LaraGram\Listening;

use BackedEnum;
use BadMethodCallException;
use Closure;
use LaraGram\Support\Arr;
use LaraGram\Support\Reflector;
use InvalidArgumentException;

/**
 * @method \LaraGram\Listening\ListenRegistrar as(string $value)
 * @method \LaraGram\Listening\ListenRegistrar controller(string $controller)
 * @method \LaraGram\Listening\ListenRegistrar middleware(array|string|null $middleware)
 * @method \LaraGram\Listening\ListenRegistrar missing(\Closure $missing)
 * @method \LaraGram\Listening\ListenRegistrar name(\BackedEnum|string $value)
 * @method \LaraGram\Listening\ListenRegistrar namespace(string|null $value)
 * @method \LaraGram\Listening\ListenRegistrar prefix(string $prefix)
 * @method \LaraGram\Listening\ListenRegistrar scopeBindings()
 * @method \LaraGram\Listening\ListenRegistrar where(array $where)
 * @method \LaraGram\Listening\ListenRegistrar withoutMiddleware(array|string $middleware)
 * @method \LaraGram\Listening\ListenRegistrar withoutScopedBindings()
 * @method \LaraGram\Listening\ListenRegistrar connection(string $name)
 * @method \LaraGram\Listening\ListenRegistrar scope(array|string $scopes)
 * @method \LaraGram\Listening\ListenRegistrar outOfScope(array|string $scopes)
 * @method \LaraGram\Listening\ListenRegistrar can(array|string $roles)
 * @method \LaraGram\Listening\ListenRegistrar canNot(array|string $roles)
 * @method \LaraGram\Listening\ListenRegistrar hasReply()
 * @method \LaraGram\Listening\ListenRegistrar hasNotReply()
 */
class ListenRegistrar
{
    use CreatesRegularExpressionListenConstraints;

    /**
     * The listener instance.
     *
     * @var \LaraGram\Listening\Listener
     */
    protected $listener;

    /**
     * The attributes to pass on to the listener.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * The methods to dynamically pass through to the listener.
     *
     * @var string[]
     */
    protected $passthru = [
        'text', 'dice', 'update', 'message', 'message_type',
        'callback_data', 'entities', 'referral', 'command',
    ];

    /**
     * The attributes that can be set through this class.
     *
     * @var string[]
     */
    protected $allowedAttributes = [
        'as',
        'connection',
        'controller',
        'middleware',
        'missing',
        'name',
        'namespace',
        'prefix',
        'scopeBindings',
        'where',
        'withoutMiddleware',
    ];

    /**
     * The attributes that are aliased.
     *
     * @var array
     */
    protected $aliases = [
        'name' => 'as',
        'scopeBindings' => 'scope_bindings',
        'withoutMiddleware' => 'excluded_middleware',
    ];

    /**
     * Create a new listen registrar instance.
     *
     * @param  \LaraGram\Listening\Listener  $listener
     * @return void
     */
    public function __construct(Listener $listener)
    {
        $this->listener = $listener;
    }

    /**
     * Set the value for a given attribute.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function attribute($key, $value)
    {
        if (! in_array($key, $this->allowedAttributes)) {
            throw new InvalidArgumentException("Attribute [{$key}] does not exist.");
        }

        if ($key === 'middleware') {
            foreach ($value as $index => $middleware) {
                $value[$index] = (string) $middleware;
            }
        }

        $attributeKey = Arr::get($this->aliases, $key, $key);

        if ($key === 'withoutMiddleware') {
            $value = array_merge(
                (array) ($this->attributes[$attributeKey] ?? []), Arr::wrap($value)
            );
        }

        if ($value instanceof BackedEnum && ! is_string($value = $value->value)) {
            throw new InvalidArgumentException("Attribute [{$key}] expects a string backed enum.");
        }

        $this->attributes[$attributeKey] = $value;

        return $this;
    }

    /**
     * Create a listen group with shared attributes.
     *
     * @param  \Closure|array|string  $callback
     * @return $this
     */
    public function group($callback)
    {
        $this->listener->group($this->attributes, $callback);

        return $this;
    }

    /**
     * Register a new listen with the given verbs.
     *
     * @param  array|string  $methods
     * @param  string  $pattern
     * @param  \Closure|array|string|null  $action
     * @return \LaraGram\Listening\Listen
     */
    public function match($methods, $pattern, $action = null)
    {
        return $this->listener->match($methods, $pattern, $this->compileAction($action));
    }

    /**
     * Register a new listen with the listener.
     *
     * @param  string  $method
     * @param  string  $pattern
     * @param  \Closure|array|string|null  $action
     * @return \LaraGram\Listening\Listen
     */
    protected function registerListen($method, $pattern, $action = null)
    {
        if (! is_array($action)) {
            $action = array_merge($this->attributes, $action ? ['uses' => $action] : []);
        }

        return $this->listener->{$method}($pattern, $this->compileAction($action));
    }

    /**
     * Compile the action into an array including the attributes.
     *
     * @param  \Closure|array|string|null  $action
     * @return array
     */
    protected function compileAction($action)
    {
        if (is_null($action)) {
            return $this->attributes;
        }

        if (is_string($action) || $action instanceof Closure) {
            $action = ['uses' => $action];
        }

        if (is_array($action) &&
            array_is_list($action) &&
            Reflector::isCallable($action)) {
            if (strncmp($action[0], '\\', 1)) {
                $action[0] = '\\'.$action[0];
            }
            $action = [
                'uses' => $action[0].'@'.$action[1],
                'controller' => $action[0].'@'.$action[1],
            ];
        }

        return array_merge($this->attributes, $action);
    }

    /**
     * Dynamically handle calls into the listen registrar.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return \LaraGram\Listening\Listen|$this
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        if (in_array($method, $this->passthru)) {
            return $this->registerListen($method, ...$parameters);
        }

        if (in_array($method, $this->allowedAttributes)) {
            if ($method === 'middleware') {
                return $this->attribute($method, is_array($parameters[0]) ? $parameters[0] : $parameters);
            }

            return $this->attribute($method, array_key_exists(0, $parameters) ? $parameters[0] : true);
        }

        throw new BadMethodCallException(sprintf(
            'Method %s::%s does not exist.', static::class, $method
        ));
    }
}
