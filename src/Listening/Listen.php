<?php

namespace LaraGram\Listening;

use BackedEnum;
use Closure;
use LaraGram\Container\Container;
use LaraGram\Listening\Matching\MethodValidator;
use LaraGram\Request\Exceptions\RequestResponseException;
use LaraGram\Request\Request;
use LaraGram\Listening\Contracts\CallableDispatcher;
use LaraGram\Listening\Contracts\ControllerDispatcher as ControllerDispatcherContract;
use LaraGram\Listening\Controllers\HasMiddleware;
use LaraGram\Listening\Controllers\Middleware;
use LaraGram\Listening\Matching\PatternValidator;
use LaraGram\Support\Arr;
use LaraGram\Support\Collection;
use LaraGram\Support\Str;
use LaraGram\Support\Traits\Conditionable;
use LaraGram\Support\Traits\Macroable;
use InvalidArgumentException;
use LaraGram\Support\SerializableClosure\SerializableClosure;
use LogicException;

use function LaraGram\Support\enum_value;

class Listen
{
    use Conditionable, CreatesRegularExpressionListenConstraints, FiltersControllerMiddleware, Macroable, ResolvesListenDependencies;

    /**
     * The pattern the listen responds to.
     *
     * @var string
     */
    public $pattern;

    /**
     * The update methods the listen responds to.
     *
     * @var array
     */
    public $methods;

    /**
     * The listen action array.
     *
     * @var array
     */
    public $action;

    /**
     * Indicates whether the listen is a fallback listen.
     *
     * @var bool
     */
    public $isFallback = false;

    /**
     * The controller instance.
     *
     * @var mixed
     */
    public $controller;

    /**
     * The default values for the listen.
     *
     * @var array
     */
    public $defaults = [];

    /**
     * The regular expression requirements.
     *
     * @var array
     */
    public $wheres = [];

    /**
     * The array of matched parameters.
     *
     * @var array|null
     */
    public $parameters;

    /**
     * The parameter names for the listen.
     *
     * @var array|null
     */
    public $parameterNames;

    /**
     * The array of the matched parameters' original values.
     *
     * @var array
     */
    protected $originalParameters;

    /**
     * Indicates "trashed" models can be retrieved when resolving implicit model bindings for this listen.
     *
     * @var bool
     */
    protected $withTrashedBindings = false;

    /**
     * Indicates the maximum number of seconds the listen should acquire a session lock for.
     *
     * @var int|null
     */
    protected $lockSeconds;

    /**
     * Indicates the maximum number of seconds the listen should wait while attempting to acquire a session lock.
     *
     * @var int|null
     */
    protected $waitSeconds;

    /**
     * The computed gathered middleware.
     *
     * @var array|null
     */
    public $computedMiddleware;

    /**
     * The compiled version of the listen.
     *
     * @var \LaraGram\Listening\CompiledListen
     */
    public $compiled;

    /**
     * The listener instance used by the listener.
     *
     * @var \LaraGram\Listening\Listener
     */
    protected $listener;

    /**
     * The container instance used by the listen.
     *
     * @var \LaraGram\Container\Container
     */
    protected $container;

    /**
     * The fields that implicit binding should use for a given parameter.
     *
     * @var array
     */
    protected $bindingFields = [];

    /**
     * The validators used by the listen.
     *
     * @var array
     */
    public static $validators;

    /**
     * All of the scopes supported by the listener.
     *
     * @var array
     */
    public static $allScopes = [
        'private', 'channel', 'group', 'supergroup', 'groups'
    ];

    /**
     * All of the user statuses supported by the listener.
     *
     * @var array
     */
    public static $allStatuses = [
        'member', 'administrator', 'creator', 'left', 'restricted', 'kicked'
    ];

    /**
     * Create a new Listen instance.
     *
     * @param  array|string  $methods
     * @param  string  $pattern
     * @param  \Closure|array  $action
     * @return void
     */
    public function __construct($methods, $pattern, $action)
    {
        $this->pattern = $pattern;
        $this->methods = (array) $methods;
        $this->action = Arr::except($this->parseAction($action), ['prefix']);

        $this->prefix(is_array($action) ? Arr::get($action, 'prefix') : '');
    }

    /**
     * Parse the listen action into a standard array.
     *
     * @param  callable|array|null  $action
     * @return array
     *
     * @throws \UnexpectedValueException
     */
    protected function parseAction($action)
    {
        return ListenAction::parse($this->pattern, $action);
    }

    /**
     * Run the listen action and return the response.
     *
     * @return mixed
     */
    public function run()
    {
        $this->container = $this->container ?: new Container;

        if (isset($this->action['connection'])){
            app('request')->connection($this->action['connection']);
        }

        try {
            if ($this->isControllerAction()) {
                return $this->runController();
            }

            return $this->runCallable();
        } catch (RequestResponseException $e) {
            return $e->getResponse();
        }
    }

    /**
     * Checks whether the listen's action is a controller.
     *
     * @return bool
     */
    protected function isControllerAction()
    {
        return is_string($this->action['uses']) && ! $this->isSerializedClosure();
    }

    /**
     * Run the listen action and return the response.
     *
     * @return mixed
     */
    protected function runCallable()
    {
        $callable = $this->action['uses'];

        if ($this->isSerializedClosure()) {
            $callable = unserialize($this->action['uses'])->getClosure();
        }

        return $this->container[CallableDispatcher::class]->dispatch($this, $callable);
    }

    /**
     * Determine if the listen action is a serialized Closure.
     *
     * @return bool
     */
    protected function isSerializedClosure()
    {
        return ListenAction::containsSerializedClosure($this->action);
    }

    /**
     * Run the listen action and return the response.
     *
     * @return mixed
     */
    protected function runController()
    {
        return $this->controllerDispatcher()->dispatch(
            $this, $this->getController(), $this->getControllerMethod()
        );
    }

    /**
     * Get the controller instance for the listen.
     *
     * @return mixed
     */
    public function getController()
    {
        if (! $this->isControllerAction()) {
            return null;
        }

        if (! $this->controller) {
            $class = $this->getControllerClass();

            $this->controller = $this->container->make(ltrim($class, '\\'));
        }

        return $this->controller;
    }

    /**
     * Get the controller class used for the listen.
     *
     * @return string|null
     */
    public function getControllerClass()
    {
        return $this->isControllerAction() ? $this->parseControllerCallback()[0] : null;
    }

    /**
     * Get the controller method used for the listen.
     *
     * @return string
     */
    protected function getControllerMethod()
    {
        return $this->parseControllerCallback()[1];
    }

    /**
     * Parse the controller.
     *
     * @return array
     */
    protected function parseControllerCallback()
    {
        return Str::parseCallback($this->action['uses']);
    }

    /**
     * Flush the cached container instance on the listen.
     *
     * @return void
     */
    public function flushController()
    {
        $this->computedMiddleware = null;
        $this->controller = null;
    }

    /**
     * Determine if the listen matches a given request.
     *
     * @param  \LaraGram\Request\Request  $request
     * @param  bool  $includingMethod
     * @return bool
     */
    public function matches(Request $request, $includingMethod = true)
    {
        $this->compileListen();

        foreach (self::getValidators() as $k => $validator) {
            if (! $includingMethod && $validator instanceof MethodValidator) {
                continue;
            }

            if (! $validator->matches($this, $request)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Compile the listen into a Symfony CompiledListen instance.
     *
     * @return \LaraGram\Listening\CompiledListen
     */
    protected function compileListen()
    {
        if (! $this->compiled) {
            $this->compiled = $this->toBaseListen()->compile();
        }

        return $this->compiled;
    }

    /**
     * Bind the listen to a given request for execution.
     *
     * @param  \LaraGram\Request\Request $request
     * @return $this
     */
    public function bind($request)
    {
        $this->compileListen();

        $this->parameters = (new ListenParameterBinder($this))
            ->parameters($request);

        $this->originalParameters = $this->parameters;

        return $this;
    }

    /**
     * Determine if the listen has parameters.
     *
     * @return bool
     */
    public function hasParameters()
    {
        return isset($this->parameters);
    }

    /**
     * Determine a given parameter exists from the listen.
     *
     * @param  string  $name
     * @return bool
     */
    public function hasParameter($name)
    {
        if ($this->hasParameters()) {
            return array_key_exists($name, $this->parameters());
        }

        return false;
    }

    /**
     * Get a given parameter from the listen.
     *
     * @param  string  $name
     * @param  string|object|null  $default
     * @return string|object|null
     */
    public function parameter($name, $default = null)
    {
        return Arr::get($this->parameters(), $name, $default);
    }

    /**
     * Get original value of a given parameter from the listen.
     *
     * @param  string  $name
     * @param  string|null  $default
     * @return string|null
     */
    public function originalParameter($name, $default = null)
    {
        return Arr::get($this->originalParameters(), $name, $default);
    }

    /**
     * Set a parameter to the given value.
     *
     * @param  string  $name
     * @param  string|object|null  $value
     * @return void
     */
    public function setParameter($name, $value)
    {
        $this->parameters();

        $this->parameters[$name] = $value;
    }

    /**
     * Unset a parameter on the listen if it is set.
     *
     * @param  string  $name
     * @return void
     */
    public function forgetParameter($name)
    {
        $this->parameters();

        unset($this->parameters[$name]);
    }

    /**
     * Get the key / value list of parameters for the listen.
     *
     * @return array
     *
     * @throws \LogicException
     */
    public function parameters()
    {
        if (isset($this->parameters)) {
            return $this->parameters;
        }

        throw new LogicException('Listen is not bound.');
    }

    /**
     * Get the key / value list of original parameters for the listen.
     *
     * @return array
     *
     * @throws \LogicException
     */
    public function originalParameters()
    {
        if (isset($this->originalParameters)) {
            return $this->originalParameters;
        }

        throw new LogicException('Listen is not bound.');
    }

    /**
     * Get the key / value list of parameters without null values.
     *
     * @return array
     */
    public function parametersWithoutNulls()
    {
        return array_filter($this->parameters(), fn ($p) => ! is_null($p));
    }

    /**
     * Get all of the parameter names for the listen.
     *
     * @return array
     */
    public function parameterNames()
    {
        if (isset($this->parameterNames)) {
            return $this->parameterNames;
        }

        return $this->parameterNames = $this->compileParameterNames();
    }

    /**
     * Get the parameter names for the listen.
     *
     * @return array
     */
    protected function compileParameterNames()
    {
        preg_match_all('/\{(.*?)\}/', $this->pattern, $matches);

        return array_map(fn ($m) => trim($m, '?'), $matches[1]);
    }

    /**
     * Get the parameters that are listed in the listen / controller signature.
     *
     * @param  array  $conditions
     * @return array
     */
    public function signatureParameters($conditions = [])
    {
        if (is_string($conditions)) {
            $conditions = ['subClass' => $conditions];
        }

        return ListenSignatureParameters::fromAction($this->action, $conditions);
    }

    /**
     * Get the binding field for the given parameter.
     *
     * @param  string|int  $parameter
     * @return string|null
     */
    public function bindingFieldFor($parameter)
    {
        $fields = is_int($parameter) ? array_values($this->bindingFields) : $this->bindingFields;

        return $fields[$parameter] ?? null;
    }

    /**
     * Get the binding fields for the listen.
     *
     * @return array
     */
    public function bindingFields()
    {
        return $this->bindingFields ?? [];
    }

    /**
     * Set the binding fields for the listen.
     *
     * @param  array  $bindingFields
     * @return $this
     */
    public function setBindingFields(array $bindingFields)
    {
        $this->bindingFields = $bindingFields;

        return $this;
    }

    /**
     * Get the parent parameter of the given parameter.
     *
     * @param  string  $parameter
     * @return string|null
     */
    public function parentOfParameter($parameter)
    {
        $key = array_search($parameter, array_keys($this->parameters));

        if ($key === 0 || $key === false) {
            return;
        }

        return array_values($this->parameters)[$key - 1];
    }

    /**
     * Allow "trashed" models to be retrieved when resolving implicit model bindings for this listen.
     *
     * @param  bool  $withTrashed
     * @return $this
     */
    public function withTrashed($withTrashed = true)
    {
        $this->withTrashedBindings = $withTrashed;

        return $this;
    }

    /**
     * Determines if the listen allows "trashed" models to be retrieved when resolving implicit model bindings.
     *
     * @return bool
     */
    public function allowsTrashedBindings()
    {
        return $this->withTrashedBindings;
    }

    /**
     * Set a default value for the listen.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function defaults($key, $value)
    {
        $this->defaults[$key] = $value;

        return $this;
    }

    /**
     * Set the default values for the listen.
     *
     * @param  array  $defaults
     * @return $this
     */
    public function setDefaults(array $defaults)
    {
        $this->defaults = $defaults;

        return $this;
    }

    /**
     * Set a regular expression requirement on the listen.
     *
     * @param  array|string  $name
     * @param  string|null  $expression
     * @return $this
     */
    public function where($name, $expression = null)
    {
        foreach ($this->parseWhere($name, $expression) as $name => $expression) {
            $this->wheres[$name] = $expression;
        }

        return $this;
    }

    /**
     * Parse arguments to the where method into an array.
     *
     * @param  array|string  $name
     * @param  string  $expression
     * @return array
     */
    protected function parseWhere($name, $expression)
    {
        return is_array($name) ? $name : [$name => $expression];
    }

    /**
     * Set a list of regular expression requirements on the listen.
     *
     * @param  array  $wheres
     * @return $this
     */
    public function setWheres(array $wheres)
    {
        foreach ($wheres as $name => $expression) {
            $this->where($name, $expression);
        }

        return $this;
    }

    /**
     * Mark this listen as a fallback listen.
     *
     * @return $this
     */
    public function fallback()
    {
        $this->isFallback = true;

        return $this;
    }

    /**
     * Set the fallback value.
     *
     * @param  bool  $isFallback
     * @return $this
     */
    public function setFallback($isFallback)
    {
        $this->isFallback = $isFallback;

        return $this;
    }

    /**
     * Get the update methods the listen responds to.
     *
     * @return array
     */
    public function methods()
    {
        return $this->methods;
    }

    /**
     * Get the prefix of the listen instance.
     *
     * @return string|null
     */
    public function getPrefix()
    {
        return $this->action['prefix'] ?? null;
    }

    /**
     * Add a prefix to the listen URI.
     *
     * @param  string|null  $prefix
     * @return $this
     */
    public function prefix($prefix)
    {
        $prefix ??= '';

        $this->updatePrefixOnAction($prefix);

        return $this->setPattern(($prefix ?? '').$this->pattern);
    }

    /**
     * Update the "prefix" attribute on the action array.
     *
     * @param  string  $prefix
     * @return void
     */
    protected function updatePrefixOnAction($prefix)
    {
        if (! empty($newPrefix = (($prefix ?? '').($this->action['prefix'] ?? '')))) {
            $this->action['prefix'] = $newPrefix;
        }
    }

    /**
     * Get the URI associated with the listen.
     *
     * @return string
     */
    public function pattern()
    {
        return $this->pattern;
    }

    /**
     * Set the URI that the listen responds to.
     *
     * @param  string  $pattern
     * @return $this
     */
    public function setPattern($pattern)
    {
        $this->pattern = $this->parsePattern($pattern);

        return $this;
    }

    /**
     * Parse the listen URI and normalize / store any implicit binding fields.
     *
     * @param  string  $pattern
     * @return string
     */
    protected function parsePattern($pattern)
    {
        $this->bindingFields = [];

        return tap(ListenPattern::parse($pattern), function ($pattern) {
            $this->bindingFields = $pattern->bindingFields;
        })->pattern;
    }

    /**
     * Get the name of the listen instance.
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->action['as'] ?? null;
    }

    /**
     * Add or change the listen name.
     *
     * @param  \BackedEnum|string  $name
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function name($name)
    {
        if ($name instanceof BackedEnum && ! is_string($name = $name->value)) {
            throw new InvalidArgumentException('Enum must be string backed.');
        }

        $this->action['as'] = isset($this->action['as']) ? $this->action['as'].$name : $name;

        return $this;
    }

    /**
     * Determine whether the listen's name matches the given patterns.
     *
     * @param  mixed  ...$patterns
     * @return bool
     */
    public function named(...$patterns)
    {
        if (is_null($listenName = $this->getName())) {
            return false;
        }

        foreach ($patterns as $pattern) {
            if (Str::is($pattern, $listenName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set the handler for the listen.
     *
     * @param  \Closure|array|string  $action
     * @return $this
     */
    public function uses($action)
    {
        if (is_array($action)) {
            $action = $action[0].'@'.$action[1];
        }

        $action = is_string($action) ? $this->addGroupNamespaceToStringUses($action) : $action;

        return $this->setAction(array_merge($this->action, $this->parseAction([
            'uses' => $action,
            'controller' => $action,
        ])));
    }

    /**
     * Parse a string based action for the "uses" fluent method.
     *
     * @param  string  $action
     * @return string
     */
    protected function addGroupNamespaceToStringUses($action)
    {
        $groupStack = last($this->listener->getGroupStack());

        if (isset($groupStack['namespace']) && ! str_starts_with($action, '\\')) {
            return $groupStack['namespace'].'\\'.$action;
        }

        return $action;
    }

    /**
     * Get the action name for the listen.
     *
     * @return string
     */
    public function getActionName()
    {
        return $this->action['controller'] ?? 'Closure';
    }

    /**
     * Get the method name of the listen action.
     *
     * @return string
     */
    public function getActionMethod()
    {
        return Arr::last(explode('@', $this->getActionName()));
    }

    /**
     * Get the action array or one of its properties for the listen.
     *
     * @param  string|null  $key
     * @return mixed
     */
    public function getAction($key = null)
    {
        return Arr::get($this->action, $key);
    }

    /**
     * Set the action array for the listen.
     *
     * @param  array  $action
     * @return $this
     */
    public function setAction(array $action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Get the value of the action that should be taken on a missing model exception.
     *
     * @return \Closure|null
     */
    public function getMissing()
    {
        $missing = $this->action['missing'] ?? null;

        return is_string($missing) &&
        Str::startsWith($missing, [
            'O:56:"LaraGram\\Support\\SerializableClosure\\SerializableClosure',
            'O:64:"LaraGram\\Support\\SerializableClosure\\UnsignedSerializableClosure',
        ]) ? unserialize($missing) : $missing;
    }

    /**
     * Define the callable that should be invoked on a missing model exception.
     *
     * @param  \Closure  $missing
     * @return $this
     */
    public function missing($missing)
    {
        $this->action['missing'] = $missing;

        return $this;
    }

    /**
     * Get all middleware, including the ones from the controller.
     *
     * @return array
     */
    public function gatherMiddleware()
    {
        if (! is_null($this->computedMiddleware)) {
            return $this->computedMiddleware;
        }

        $this->computedMiddleware = [];

        return $this->computedMiddleware = Listener::uniqueMiddleware(array_merge(
            $this->middleware(), $this->controllerMiddleware()
        ));
    }

    /**
     * Get or set the middlewares attached to the listen.
     *
     * @param  array|string|null  $middleware
     * @return $this|array
     */
    public function middleware($middleware = null)
    {
        if (is_null($middleware)) {
            return (array) ($this->action['middleware'] ?? []);
        }

        if (! is_array($middleware)) {
            $middleware = func_get_args();
        }

        foreach ($middleware as $index => $value) {
            $middleware[$index] = (string) $value;
        }

        $this->action['middleware'] = array_merge(
            (array) ($this->action['middleware'] ?? []), $middleware
        );

        return $this;
    }

    /**
     * Specify that the "Authorize" / "can" middleware should be applied to the listen with the given options.
     *
     * @param  \UnitEnum|string  $ability
     * @param  array|string  $models
     * @return $this
     */
    public function can($ability, $models = [])
    {
        $ability = enum_value($ability);

        return empty($models)
            ? $this->middleware(['can:'.$ability])
            : $this->middleware(['can:'.implode('|', $ability).','.implode(',', Arr::wrap($models))]);
    }

    /**
     * Specify that the "Authorize" / "can" middleware should be applied to the listen with the given options.
     *
     * @param  \UnitEnum|string  $ability
     * @param  array|string  $models
     * @return $this
     */
    public function canNot($ability, $models = [])
    {
        $ability = Arr::wrap(enum_value($ability));
        $ability = array_values(array_diff(self::$allStatuses, $ability));

        return empty($models)
            ? $this->middleware(['can:'.implode('|', $ability)])
            : $this->middleware(['can:'.implode('|', $ability).','.implode(',', Arr::wrap($models))]);
    }

    /**
     * Specify that "scope" middleware should be applied to the listen with the given options.
     *
     * @param  \UnitEnum|string $scope
     * @return $this
     */
    public function scope($scope)
    {
        $scope = collect(enum_value($scope))
            ->flatMap(function ($scope) {
                return $scope === 'groups'
                    ? ['group', 'supergroup']
                    : [$scope];
            })
            ->unique()
            ->values()
            ->all();

        return $this->middleware(['scope:'.implode(',', $scope)]);
    }

    /**
     * Specify that "scope" middleware should be applied to the listen with the given options.
     *
     * @param  \UnitEnum|string $scope
     * @return $this
     */
    public function outOfScope($scope)
    {
        $toExclude = collect(enum_value($scope))->flatMap(function ($scope) {
            if ($scope === 'groups') {
                return ['group', 'supergroup', 'groups'];
            }

            if (in_array($scope, ['group', 'supergroup'])) {
                return [$scope, 'groups'];
            }

            return [$scope];
        })->unique();

        $scope = collect(self::$allScopes)
            ->diff($toExclude)
            ->values()
            ->all();

        return $this->middleware(['scope:'.implode(',', $scope)]);
    }

    /**
     * Specify that "reply:true" middleware should be applied to the listen with the given options.
     *
     * @return $this
     */
    public function hasReply()
    {
        return $this->middleware(['reply:true']);
    }

    /**
     * Specify that "reply:false" middleware should be applied to the listen with the given options.
     *
     * @return $this
     */
    public function hasNotReply()
    {
        return $this->middleware(['reply:false']);
    }

    /**
     * Specify that "step" middleware should be applied to the listen with the given options.
     *
     * @return $this
     */
    public function step($key)
    {
        return $this->middleware(['step:'.$key]);
    }

    /**
     * Set bot connection instance for listen.
     *
     * @param  string $connection
     * @return $this
     */
    public function connection($connection)
    {
        if (blank($connection))
            throw new \InvalidArgumentException('Connection name can not be empty');

        $this->action['connection'] = $connection;

        return $this;
    }

    /**
     * Get the connection instance for listen.
     *
     * @return string|null
     */
    public function getConnection()
    {
        return $this->action['connection'] ?? null;
    }

    /**
     * Get the middleware for the listen's controller.
     *
     * @return array
     */
    public function controllerMiddleware()
    {
        if (! $this->isControllerAction()) {
            return [];
        }

        [$controllerClass, $controllerMethod] = [
            $this->getControllerClass(),
            $this->getControllerMethod(),
        ];


        if (is_a($controllerClass, HasMiddleware::class, true)) {
            return $this->staticallyProvidedControllerMiddleware(
                $controllerClass, $controllerMethod
            );
        }

        if (method_exists($controllerClass, 'getMiddleware')) {
            return $this->controllerDispatcher()->getMiddleware(
                $this->getController(), $controllerMethod
            );
        }

        return [];
    }

    /**
     * Get the statically provided controller middleware for the given class and method.
     *
     * @param  string  $class
     * @param  string  $method
     * @return array
     */
    protected function staticallyProvidedControllerMiddleware(string $class, string $method)
    {
        return (new Collection($class::middleware()))->map(function ($middleware) {
            return $middleware instanceof Middleware
                ? $middleware
                : new Middleware($middleware);
        })->reject(function ($middleware) use ($method) {
            return static::methodExcludedByOptions(
                $method, ['only' => $middleware->only, 'except' => $middleware->except]
            );
        })->map->middleware->flatten()->values()->all();
    }

    /**
     * Specify middleware that should be removed from the given listen.
     *
     * @param  array|string  $middleware
     * @return $this
     */
    public function withoutMiddleware($middleware)
    {
        $this->action['excluded_middleware'] = array_merge(
            (array) ($this->action['excluded_middleware'] ?? []), Arr::wrap($middleware)
        );

        return $this;
    }

    /**
     * Get the middleware that should be removed from the listen.
     *
     * @return array
     */
    public function excludedMiddleware()
    {
        return (array) ($this->action['excluded_middleware'] ?? []);
    }

    /**
     * Indicate that the listen should enforce scoping of multiple implicit Eloquent bindings.
     *
     * @return $this
     */
    public function scopeBindings()
    {
        $this->action['scope_bindings'] = true;

        return $this;
    }

    /**
     * Indicate that the listen should not enforce scoping of multiple implicit Eloquent bindings.
     *
     * @return $this
     */
    public function withoutScopedBindings()
    {
        $this->action['scope_bindings'] = false;

        return $this;
    }

    /**
     * Determine if the listen should enforce scoping of multiple implicit Eloquent bindings.
     *
     * @return bool
     */
    public function enforcesScopedBindings()
    {
        return (bool) ($this->action['scope_bindings'] ?? false);
    }

    /**
     * Determine if the listen should prevent scoping of multiple implicit Eloquent bindings.
     *
     * @return bool
     */
    public function preventsScopedBindings()
    {
        return isset($this->action['scope_bindings']) && $this->action['scope_bindings'] === false;
    }

    /**
     * Specify that the listen should not allow concurrent requests from the same session.
     *
     * @param  int|null  $lockSeconds
     * @param  int|null  $waitSeconds
     * @return $this
     */
    public function block($lockSeconds = 10, $waitSeconds = 10)
    {
        $this->lockSeconds = $lockSeconds;
        $this->waitSeconds = $waitSeconds;

        return $this;
    }

    /**
     * Specify that the listen should allow concurrent requests from the same session.
     *
     * @return $this
     */
    public function withoutBlocking()
    {
        return $this->block(null, null);
    }

    /**
     * Get the maximum number of seconds the listen's session lock should be held for.
     *
     * @return int|null
     */
    public function locksFor()
    {
        return $this->lockSeconds;
    }

    /**
     * Get the maximum number of seconds to wait while attempting to acquire a session lock.
     *
     * @return int|null
     */
    public function waitsFor()
    {
        return $this->waitSeconds;
    }

    /**
     * Get the dispatcher for the listen's controller.
     *
     * @return \LaraGram\Listening\Contracts\ControllerDispatcher
     */
    public function controllerDispatcher()
    {
        if ($this->container->bound(ControllerDispatcherContract::class)) {
            return $this->container->make(ControllerDispatcherContract::class);
        }

        return new ControllerDispatcher($this->container);
    }

    /**
     * Get the listen validators for the instance.
     *
     * @return array
     */
    public static function getValidators()
    {
        if (isset(static::$validators)) {
            return static::$validators;
        }

        // To match the listen, we will use a chain of responsibility pattern with the
        // validator implementations. We will spin through each one making sure it
        // passes and then we will know if the listen as a whole matches request.
        return static::$validators = [
            new MethodValidator, new PatternValidator,
        ];
    }

    /**
     * Convert the listen to a Symfony listen.
     *
     * @return \LaraGram\Listening\BaseListen
     */
    public function toBaseListen()
    {
        return new BaseListen(
            preg_replace('/\{(\w+?)\?\}/', '{$1}', $this->pattern()), $this->getOptionalParameterNames(),
            $this->wheres, ['utf8' => true],
            $this->methods
        );
    }

    /**
     * Get the optional parameter names for the listen.
     *
     * @return array
     */
    protected function getOptionalParameterNames()
    {
        preg_match_all('/\{(\w+?)\?\}/', $this->pattern(), $matches);

        return isset($matches[1]) ? array_fill_keys($matches[1], null) : [];
    }

    /**
     * Get the compiled version of the listen.
     *
     * @return \LaraGram\Listening\CompiledListen
     */
    public function getCompiled()
    {
        return $this->compiled;
    }

    /**
     * Set the listener instance on the listen.
     *
     * @param  \LaraGram\Listening\Listener  $listener
     * @return $this
     */
    public function setListener(Listener $listener)
    {
        $this->listener = $listener;

        return $this;
    }

    /**
     * Set the container instance on the listen.
     *
     * @param  \LaraGram\Container\Container  $container
     * @return $this
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Prepare the listen instance for serialization.
     *
     * @return void
     *
     * @throws \LogicException
     */
    public function prepareForSerialization()
    {
        if ($this->action['uses'] instanceof Closure) {
            $this->action['uses'] = serialize(
                SerializableClosure::unsigned($this->action['uses'])
            );
        }

        if (isset($this->action['missing']) && $this->action['missing'] instanceof Closure) {
            $this->action['missing'] = serialize(
                SerializableClosure::unsigned($this->action['missing'])
            );
        }

        $this->compileListen();

        unset($this->listener, $this->container);
    }

    /**
     * Dynamically access listen parameters.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->parameter($key);
    }
}
