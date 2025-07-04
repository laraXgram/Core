<?php

namespace LaraGram\Listening;

use ArrayObject;
use Closure;
use LaraGram\Container\Container;
use LaraGram\Contracts\Events\Dispatcher;
use LaraGram\Contracts\Listening\BindingRegistrar;
use LaraGram\Contracts\Listening\Registrar as RegistrarContract;
use LaraGram\Contracts\Support\Arrayable;
use LaraGram\Contracts\Support\Jsonable;
use LaraGram\Contracts\Support\Responsable;
use LaraGram\Database\Eloquent\Model;
use LaraGram\Request\JsonResponse;
use LaraGram\Request\Request;
use LaraGram\Request\Response;
use LaraGram\Listening\Events\PreparingResponse;
use LaraGram\Listening\Events\ResponsePrepared;
use LaraGram\Listening\Events\ListenMatched;
use LaraGram\Listening\Events\Listening;
use LaraGram\Support\Arr;
use LaraGram\Support\Collection;
use LaraGram\Support\Str;
use LaraGram\Support\Stringable;
use LaraGram\Support\Traits\Macroable;
use LaraGram\Support\Traits\Tappable;
use JsonSerializable;
use ReflectionClass;
use stdClass;
use LaraGram\Request\Response as SymfonyResponse;

/**
 * @mixin \LaraGram\Listening\ListenRegistrar
 */
class Listener implements BindingRegistrar, RegistrarContract
{
    use Macroable {
        __call as macroCall;
    }
    use Tappable;

    /**
     * The event dispatcher instance.
     *
     * @var \LaraGram\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * The IoC container instance.
     *
     * @var \LaraGram\Container\Container
     */
    protected $container;

    /**
     * The listen collection instance.
     *
     * @var \LaraGram\Listening\ListenCollectionInterface
     */
    protected $listens;

    /**
     * The currently dispatched listen instance.
     *
     * @var \LaraGram\Listening\Listen|null
     */
    protected $current;

    /**
     * The request currently being dispatched.
     *
     * @var \LaraGram\Request\Request
     */
    protected $currentRequest;

    /**
     * All of the short-hand keys for middlewares.
     *
     * @var array
     */
    protected $middleware = [];

    /**
     * All of the middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [];

    /**
     * The priority-sorted list of middleware.
     *
     * Forces the listed middleware to always be in the given order.
     *
     * @var array
     */
    public $middlewarePriority = [];

    /**
     * The registered listen value binders.
     *
     * @var array
     */
    protected $binders = [];

    /**
     * The globally available parameter patterns.
     *
     * @var array
     */
    protected $patterns = [];


    /**
     * The laraquest connection instance.
     *
     * @var string
     */
    protected $connection = 'bot';

    /**
     * The listen group attribute stack.
     *
     * @var array
     */
    protected $groupStack = [];

    /**
     * The registered custom implicit binding callback.
     *
     * @var array
     */
    protected $implicitBindingCallback;

    /**
     * All of the verbs supported by the listener.
     *
     * @var string[]
     */
    public static $verbs = [
        'TEXT', 'COMMAND', 'DICE', 'MEDIA', 'UPDATE',
        'MESSAGE', 'MESSAGE_TYPE', 'CALLBACK_DATA',
        'REFERRAL', 'HASHTAG', 'CASHTAG',
        'MENTION', 'ADD_MEMBER', 'JOIN_MEMBER',
    ];

    /**
     * Create a new Listener instance.
     *
     * @param  \LaraGram\Contracts\Events\Dispatcher  $events
     * @param  \LaraGram\Container\Container|null  $container
     * @return void
     */
    public function __construct(Dispatcher $events, ?Container $container = null)
    {
        $this->events = $events;
        $this->listens = new ListenCollection;
        $this->container = $container ?: new Container;
    }

    /**
     * Register a new GET listen with the listener.
     *
     * @param  string  $pattern
     * @param  array|string|callable  $action
     * @return \LaraGram\Listening\Listen
     */
    public function onText($pattern, $action)
    {
        return $this->addListen('TEXT', $pattern, $action);
    }

    /**
     * Register a new listen responding to all verbs.
     *
     * @param  string  $pattern
     * @param  array|string|callable|null  $action
     * @return \LaraGram\Listening\Listen
     */
    public function onAny($action = null)
    {
        return $this->addListen(self::$verbs, '', $action);
    }

    /**
     * Register a new fallback listen with the listener.
     *
     * @param  array|string|callable|null  $action
     * @return \LaraGram\Listening\Listen
     */
    public function fallback($action)
    {
        $placeholder = 'fallbackPlaceholder';

        return $this->addListen(
            self::$verbs, "{{$placeholder}}", $action
        )->where($placeholder, '.*')->fallback();
    }

    /**
     * Create a redirect from one URI to another.
     *
     * @param  string  $pattern
     * @param  string  $destination
     * @return \LaraGram\Listening\Listen
     */
    public function redirect($pattern, $destination)
    {
        return $this->addListen(self::$verbs, $pattern, '\LaraGram\Listening\RedirectController')
            ->defaults('destination', $destination);
    }

    /**
     * Register a new listen that returns a view.
     *
     * @param  string  $pattern
     * @param  string  $view
     * @param  array  $data
     * @param  int|array  $status
     * @param  array  $headers
     * @return \LaraGram\Listening\Listen
     */
    public function template($pattern, $template, $data = [])
    {
        return $this->addListen(self::$verbs, $pattern, '\LaraGram\Listening\TemplateController')
            ->setDefaults([
                'template' => $template,
                'data' => $data,
            ]);
    }

    /**
     * Register a new listen with the given verbs.
     *
     * @param  array|string  $methods
     * @param  string  $pattern
     * @param  array|string|callable|null  $action
     * @return \LaraGram\Listening\Listen
     */
    public function match($methods, $pattern, $action = null)
    {
        return $this->addListen(array_map(strtoupper(...), (array) $methods), $pattern, $action);
    }

    /**
     * Create a listen group with shared attributes.
     *
     * @param  array  $attributes
     * @param  \Closure|array|string  $listens
     * @return $this
     */
    public function group(array $attributes, $listens)
    {
        foreach (Arr::wrap($listens) as $groupListens) {
            $this->updateGroupStack($attributes);

            // Once we have updated the group stack, we'll load the provided listens and
            // merge in the group's attributes when the listens are created. After we
            // have created the listens, we will pop the attributes off the stack.
            $this->loadListens($groupListens);

            array_pop($this->groupStack);
        }

        return $this;
    }

    /**
     * Update the group stack with the given attributes.
     *
     * @param  array  $attributes
     * @return void
     */
    protected function updateGroupStack(array $attributes)
    {
        if ($this->hasGroupStack()) {
            $attributes = $this->mergeWithLastGroup($attributes);
        }

        $this->groupStack[] = $attributes;
    }

    /**
     * Merge the given array with the last group stack.
     *
     * @param  array  $new
     * @param  bool  $prependExistingPrefix
     * @return array
     */
    public function mergeWithLastGroup($new, $prependExistingPrefix = true)
    {
        return ListenGroup::merge($new, end($this->groupStack), $prependExistingPrefix);
    }

    /**
     * Load the provided listens.
     *
     * @param  \Closure|string  $listens
     * @return void
     */
    protected function loadListens($listens)
    {
        if ($listens instanceof Closure) {
            $listens($this);
        } else {
            (new ListenFileRegistrar($this))->register($listens);
        }
    }

    /**
     * Get the prefix from the last group on the stack.
     *
     * @return string
     */
    public function getLastGroupPrefix()
    {
        if ($this->hasGroupStack()) {
            $last = end($this->groupStack);

            return $last['prefix'] ?? '';
        }

        return '';
    }

    /**
     * Add a listen to the underlying listen collection.
     *
     * @param  array|string  $methods
     * @param  string  $pattern
     * @param  array|string|callable|null  $action
     * @return \LaraGram\Listening\Listen
     */
    public function addListen($methods, $pattern, $action)
    {
        return $this->listens->add($this->createListen($methods, $pattern, $action));
    }

    /**
     * Create a new listen instance.
     *
     * @param  array|string  $methods
     * @param  string  $pattern
     * @param  mixed  $action
     * @return \LaraGram\Listening\Listen
     */
    protected function createListen($methods, $pattern, $action)
    {
        // If the listen is listening to a controller we will parse the listen action into
        // an acceptable array format before registering it and creating this listen
        // instance itself. We need to build the Closure that will call this out.
        if ($this->actionReferencesController($action)) {
            $action = $this->convertToControllerAction($action);
        }

        $listen = $this->newListen(
            $methods, $this->prefix($pattern), $action
        );

        // If we have groups that need to be merged, we will merge them now after this
        // listen has already been created and is ready to go. After we're done with
        // the merge we will be ready to return the listen back out to the caller.
        if ($this->hasGroupStack()) {
            $this->mergeGroupAttributesIntoListen($listen);
        }

        $this->addWhereClausesToListen($listen);

        return $listen;
    }

    /**
     * Determine if the action is listening to a controller.
     *
     * @param  mixed  $action
     * @return bool
     */
    protected function actionReferencesController($action)
    {
        if (! $action instanceof Closure) {
            return is_string($action) || (isset($action['uses']) && is_string($action['uses']));
        }

        return false;
    }

    /**
     * Add a controller based listen action to the action array.
     *
     * @param  array|string  $action
     * @return array
     */
    protected function convertToControllerAction($action)
    {
        if (is_string($action)) {
            $action = ['uses' => $action];
        }

        // Here we'll merge any group "controller" and "uses" statements if necessary so that
        // the action has the proper clause for this property. Then, we can simply set the
        // name of this controller on the action plus return the action array for usage.
        if ($this->hasGroupStack()) {
            $action['uses'] = $this->prependGroupController($action['uses']);
            $action['uses'] = $this->prependGroupNamespace($action['uses']);
        }

        // Here we will set this controller name on the action array just so we always
        // have a copy of it for reference if we need it. This can be used while we
        // search for a controller name or do some other type of fetch operation.
        $action['controller'] = $action['uses'];

        return $action;
    }

    /**
     * Prepend the last group namespace onto the use clause.
     *
     * @param  string  $class
     * @return string
     */
    protected function prependGroupNamespace($class)
    {
        $group = end($this->groupStack);

        return isset($group['namespace']) && ! str_starts_with($class, '\\') && ! str_starts_with($class, $group['namespace'])
            ? $group['namespace'].'\\'.$class : $class;
    }

    /**
     * Prepend the last group controller onto the use clause.
     *
     * @param  string  $class
     * @return string
     */
    protected function prependGroupController($class)
    {
        $group = end($this->groupStack);

        if (! isset($group['controller'])) {
            return $class;
        }

        if (class_exists($class)) {
            return $class;
        }

        if (str_contains($class, '@')) {
            return $class;
        }

        return $group['controller'].'@'.$class;
    }

    /**
     * Create a new Listen object.
     *
     * @param  array|string  $methods
     * @param  string  $pattern
     * @param  mixed  $action
     * @return \LaraGram\Listening\Listen
     */
    public function newListen($methods, $pattern, $action)
    {
        return (new Listen($methods, $pattern, $action))
            ->setListener($this)
            ->setContainer($this->container);
    }

    /**
     * Prefix the given URI with the last prefix.
     *
     * @param  string  $pattern
     * @return string
     */
    protected function prefix($pattern)
    {
        $prefix = $this->getLastGroupPrefix();
        return ($prefix === '' ? '' : "$prefix ").$pattern ?: '';
    }

    /**
     * Add the necessary where clauses to the listen based on its initial registration.
     *
     * @param  \LaraGram\Listening\Listen  $listen
     * @return \LaraGram\Listening\Listen
     */
    protected function addWhereClausesToListen($listen)
    {
        $listen->where(array_merge(
            $this->patterns, $listen->getAction()['where'] ?? []
        ));

        return $listen;
    }

    /**
     * Merge the group stack with the controller action.
     *
     * @param  \LaraGram\Listening\Listen  $listen
     * @return void
     */
    protected function mergeGroupAttributesIntoListen($listen)
    {
        $listen->setAction($this->mergeWithLastGroup(
            $listen->getAction(),
            prependExistingPrefix: false
        ));
    }

    /**
     * Return the response returned by the given listen.
     *
     * @param  string  $name
     * @return \LaraGram\Request\Response
     */
    public function respondWithListen($name)
    {
        $listen = tap($this->listens->getByName($name))->bind($this->currentRequest);

        return $this->runListen($this->currentRequest, $listen);
    }

    /**
     * Dispatch the request to the application.
     *
     * @param  \LaraGram\Request\Request  $request
     * @return \LaraGram\Request\Response
     */
    public function dispatch(Request $request)
    {
        $this->currentRequest = $request;

        return $this->dispatchToListen($request);
    }

    /**
     * Dispatch the request to a listen and return the response.
     *
     * @param  \LaraGram\Request\Request  $request
     * @return \LaraGram\Request\Response
     */
    public function dispatchToListen(Request $request)
    {
        return $this->runListen($request, $this->findListen($request));
    }

    /**
     * Find the listen matching a given request.
     *
     * @param  \LaraGram\Request\Request  $request
     * @return \LaraGram\Listening\Listen
     */
    protected function findListen($request)
    {
        $this->events->dispatch(new Listening($request));

        $this->current = $listen = $this->listens->match($request);

        $listen->setContainer($this->container);

        $this->container->instance(Listen::class, $listen);

        return $listen;
    }

    /**
     * Return the response for the given listen.
     *
     * @param  \LaraGram\Request\Request  $request
     * @param  \LaraGram\Listening\Listen  $listen
     * @return \LaraGram\Request\Response
     */
    protected function runListen(Request $request, Listen $listen)
    {
        $request->setListenResolver(fn () => $listen);

        $this->events->dispatch(new ListenMatched($listen, $request));

        return $this->prepareResponse($request,
            $this->runListenWithinStack($listen, $request)
        );
    }

    /**
     * Run the given listen within a Stack "onion" instance.
     *
     * @param  \LaraGram\Listening\Listen  $listen
     * @param  \LaraGram\Request\Request  $request
     * @return mixed
     */
    protected function runListenWithinStack(Listen $listen, Request $request)
    {
        $shouldSkipMiddleware = $this->container->bound('middleware.disable') &&
            $this->container->make('middleware.disable') === true;

        $middleware = $shouldSkipMiddleware ? [] : $this->gatherListenMiddleware($listen);

        return (new Pipeline($this->container))
            ->send($request)
            ->through($middleware)
            ->then(fn ($request) => $this->prepareResponse(
                $request, $listen->run()
            ));
    }

    /**
     * Gather the middleware for the given listen with resolved class names.
     *
     * @param  \LaraGram\Listening\Listen  $listen
     * @return array
     */
    public function gatherListenMiddleware(Listen $listen)
    {
        return $this->resolveMiddleware($listen->gatherMiddleware(), $listen->excludedMiddleware());
    }

    /**
     * Resolve a flat array of middleware classes from the provided array.
     *
     * @param  array  $middleware
     * @param  array  $excluded
     * @return array
     */
    public function resolveMiddleware(array $middleware, array $excluded = [])
    {
        $excluded = (new Collection($excluded))->map(function ($name) {
            return (array) MiddlewareNameResolver::resolve($name, $this->middleware, $this->middlewareGroups);
        })->flatten()->values()->all();

        $middleware = (new Collection($middleware))->map(function ($name) {
            return (array) MiddlewareNameResolver::resolve($name, $this->middleware, $this->middlewareGroups);
        })->flatten()->reject(function ($name) use ($excluded) {
            if (empty($excluded)) {
                return false;
            }

            if ($name instanceof Closure) {
                return false;
            }

            if (in_array($name, $excluded, true)) {
                return true;
            }

            if (! class_exists($name)) {
                return false;
            }

            $reflection = new ReflectionClass($name);

            return (new Collection($excluded))->contains(
                fn ($exclude) => class_exists($exclude) && $reflection->isSubclassOf($exclude)
            );
        })->values();

        return $this->sortMiddleware($middleware);
    }

    /**
     * Sort the given middleware by priority.
     *
     * @param  \LaraGram\Support\Collection  $middlewares
     * @return array
     */
    protected function sortMiddleware(Collection $middlewares)
    {
        return (new SortedMiddleware($this->middlewarePriority, $middlewares))->all();
    }

    /**
     * Create a response instance from the given value.
     *
     * @param  \LaraGram\Request\Request  $request
     * @param  mixed  $response
     * @return \LaraGram\Request\Response
     */
    public function prepareResponse($request, $response)
    {
        $this->events->dispatch(new PreparingResponse($request, $response));

        return tap(static::toResponse($request, $response), function ($response) use ($request) {
            $this->events->dispatch(new ResponsePrepared($request, $response));
        });
    }

    /**
     * Static version of prepareResponse.
     *
     * @param  \LaraGram\Request\Request  $request
     * @param  mixed  $response
     * @return \LaraGram\Request\Response
     */
    public static function toResponse($request, $response)
    {
        if ($response instanceof Responsable) {
            $response = $response->toResponse($request);
        }

        if ($response instanceof Model && $response->wasRecentlyCreated) {
            $response = new JsonResponse($response, 201);
        } elseif ($response instanceof Stringable) {
            $response = new Response($response->__toString());
        } elseif (! $response instanceof SymfonyResponse &&
            ($response instanceof Arrayable ||
                $response instanceof Jsonable ||
                $response instanceof ArrayObject ||
                $response instanceof JsonSerializable ||
                $response instanceof stdClass ||
                is_array($response))) {
            $response = new JsonResponse($response);
        } elseif (! $response instanceof SymfonyResponse) {
            $response = new Response(json_encode($response));
        }

        return $response;
    }

    /**
     * Substitute the listen bindings onto the listen.
     *
     * @param  \LaraGram\Listening\Listen  $listen
     * @return \LaraGram\Listening\Listen
     *
     * @throws \LaraGram\Database\Eloquent\ModelNotFoundException<\LaraGram\Database\Eloquent\Model>
     * @throws \LaraGram\Listening\Exceptions\BackedEnumCaseNotFoundException
     */
    public function substituteBindings($listen)
    {
        foreach ($listen->parameters() as $key => $value) {
            if (isset($this->binders[$key])) {
                $listen->setParameter($key, $this->performBinding($key, $value, $listen));
            }
        }

        return $listen;
    }

    /**
     * Substitute the implicit listen bindings for the given listen.
     *
     * @param  \LaraGram\Listening\Listen  $listen
     * @return void
     *
     * @throws \LaraGram\Database\Eloquent\ModelNotFoundException<\LaraGram\Database\Eloquent\Model>
     * @throws \LaraGram\Listening\Exceptions\BackedEnumCaseNotFoundException
     */
    public function substituteImplicitBindings($listen)
    {
        $default = fn () => ImplicitListenBinding::resolveForListen($this->container, $listen);

        return call_user_func(
            $this->implicitBindingCallback ?? $default, $this->container, $listen, $default
        );
    }

    /**
     * Register a callback to run after implicit bindings are substituted.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function substituteImplicitBindingsUsing($callback)
    {
        $this->implicitBindingCallback = $callback;

        return $this;
    }

    /**
     * Call the binding callback for the given key.
     *
     * @param  string  $key
     * @param  string  $value
     * @param  \LaraGram\Listening\Listen  $listen
     * @return mixed
     *
     * @throws \LaraGram\Database\Eloquent\ModelNotFoundException<\LaraGram\Database\Eloquent\Model>
     */
    protected function performBinding($key, $value, $listen)
    {
        return call_user_func($this->binders[$key], $value, $listen);
    }

    /**
     * Register a listen matched event listener.
     *
     * @param  string|callable  $callback
     * @return void
     */
    public function matched($callback)
    {
        $this->events->listen(Events\ListenMatched::class, $callback);
    }

    /**
     * Get all of the defined middleware short-hand names.
     *
     * @return array
     */
    public function getMiddleware()
    {
        return $this->middleware;
    }

    /**
     * Register a short-hand name for a middleware.
     *
     * @param  string  $name
     * @param  string  $class
     * @return $this
     */
    public function aliasMiddleware($name, $class)
    {
        $this->middleware[$name] = $class;

        return $this;
    }

    /**
     * Check if a middlewareGroup with the given name exists.
     *
     * @param  string  $name
     * @return bool
     */
    public function hasMiddlewareGroup($name)
    {
        return array_key_exists($name, $this->middlewareGroups);
    }

    /**
     * Get all of the defined middleware groups.
     *
     * @return array
     */
    public function getMiddlewareGroups()
    {
        return $this->middlewareGroups;
    }

    /**
     * Register a group of middleware.
     *
     * @param  string  $name
     * @param  array  $middleware
     * @return $this
     */
    public function middlewareGroup($name, array $middleware)
    {
        $this->middlewareGroups[$name] = $middleware;

        return $this;
    }

    /**
     * Add a middleware to the beginning of a middleware group.
     *
     * If the middleware is already in the group, it will not be added again.
     *
     * @param  string  $group
     * @param  string  $middleware
     * @return $this
     */
    public function prependMiddlewareToGroup($group, $middleware)
    {
        if (isset($this->middlewareGroups[$group]) && ! in_array($middleware, $this->middlewareGroups[$group])) {
            array_unshift($this->middlewareGroups[$group], $middleware);
        }

        return $this;
    }

    /**
     * Add a middleware to the end of a middleware group.
     *
     * If the middleware is already in the group, it will not be added again.
     *
     * @param  string  $group
     * @param  string  $middleware
     * @return $this
     */
    public function pushMiddlewareToGroup($group, $middleware)
    {
        if (! array_key_exists($group, $this->middlewareGroups)) {
            $this->middlewareGroups[$group] = [];
        }

        if (! in_array($middleware, $this->middlewareGroups[$group])) {
            $this->middlewareGroups[$group][] = $middleware;
        }

        return $this;
    }

    /**
     * Remove the given middleware from the specified group.
     *
     * @param  string  $group
     * @param  string  $middleware
     * @return $this
     */
    public function removeMiddlewareFromGroup($group, $middleware)
    {
        if (! $this->hasMiddlewareGroup($group)) {
            return $this;
        }

        $reversedMiddlewaresArray = array_flip($this->middlewareGroups[$group]);

        if (! array_key_exists($middleware, $reversedMiddlewaresArray)) {
            return $this;
        }

        $middlewareKey = $reversedMiddlewaresArray[$middleware];

        unset($this->middlewareGroups[$group][$middlewareKey]);

        return $this;
    }

    /**
     * Flush the listener's middleware groups.
     *
     * @return $this
     */
    public function flushMiddlewareGroups()
    {
        $this->middlewareGroups = [];

        return $this;
    }

    /**
     * Add a new listen parameter binder.
     *
     * @param  string  $key
     * @param  string|callable  $binder
     * @return void
     */
    public function bind($key, $binder)
    {
        $this->binders[str_replace('-', '_', $key)] = ListenBinding::forCallback(
            $this->container, $binder
        );
    }

    /**
     * Register a model binder for a wildcard.
     *
     * @param  string  $key
     * @param  string  $class
     * @param  \Closure|null  $callback
     * @return void
     */
    public function model($key, $class, ?Closure $callback = null)
    {
        $this->bind($key, ListenBinding::forModel($this->container, $class, $callback));
    }

    /**
     * Get the binding callback for a given binding.
     *
     * @param  string  $key
     * @return \Closure|null
     */
    public function getBindingCallback($key)
    {
        if (isset($this->binders[$key = str_replace('-', '_', $key)])) {
            return $this->binders[$key];
        }
    }

    /**
     * Get the global "where" patterns.
     *
     * @return array
     */
    public function getPatterns()
    {
        return $this->patterns;
    }

    /**
     * Set a global where pattern on all listens.
     *
     * @param  string  $key
     * @param  string  $pattern
     * @return void
     */
    public function pattern($key, $pattern)
    {
        $this->patterns[$key] = $pattern;
    }

    /**
     * Set a group of global where patterns on all listens.
     *
     * @param  array  $patterns
     * @return void
     */
    public function patterns($patterns)
    {
        foreach ($patterns as $key => $pattern) {
            $this->pattern($key, $pattern);
        }
    }

    /**
     * Determine if the listener currently has a group stack.
     *
     * @return bool
     */
    public function hasGroupStack()
    {
        return ! empty($this->groupStack);
    }

    /**
     * Get the current group stack for the listener.
     *
     * @return array
     */
    public function getGroupStack()
    {
        return $this->groupStack;
    }

    /**
     * Get a listen parameter for the current listen.
     *
     * @param  string  $key
     * @param  string|null  $default
     * @return mixed
     */
    public function input($key, $default = null)
    {
        return $this->current()->parameter($key, $default);
    }

    /**
     * Get the request currently being dispatched.
     *
     * @return \LaraGram\Request\Request
     */
    public function getCurrentRequest()
    {
        return $this->currentRequest;
    }

    /**
     * Get the currently dispatched listen instance.
     *
     * @return \LaraGram\Listening\Listen|null
     */
    public function getCurrentListen()
    {
        return $this->current();
    }

    /**
     * Get the currently dispatched listen instance.
     *
     * @return \LaraGram\Listening\Listen|null
     */
    public function current()
    {
        return $this->current;
    }

    /**
     * Check if a listen with the given name exists.
     *
     * @param  string|array  $name
     * @return bool
     */
    public function has($name)
    {
        $names = is_array($name) ? $name : func_get_args();

        foreach ($names as $value) {
            if (! $this->listens->hasNamedListen($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the current listen name.
     *
     * @return string|null
     */
    public function currentListenName()
    {
        return $this->current()?->getName();
    }

    /**
     * Alias for the "currentListenNamed" method.
     *
     * @param  mixed  ...$patterns
     * @return bool
     */
    public function is(...$patterns)
    {
        return $this->currentListenNamed(...$patterns);
    }

    /**
     * Determine if the current listen matches a pattern.
     *
     * @param  mixed  ...$patterns
     * @return bool
     */
    public function currentListenNamed(...$patterns)
    {
        return $this->current() && $this->current()->named(...$patterns);
    }

    /**
     * Get the current listen action.
     *
     * @return string|null
     */
    public function currentListenAction()
    {
        if ($this->current()) {
            return $this->current()->getAction()['controller'] ?? null;
        }
    }

    /**
     * Alias for the "currentListenUses" method.
     *
     * @param  array|string  ...$patterns
     * @return bool
     */
    public function uses(...$patterns)
    {
        foreach ($patterns as $pattern) {
            if (Str::is($pattern, $this->currentListenAction())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the current listen action matches a given action.
     *
     * @param  string  $action
     * @return bool
     */
    public function currentListenUses($action)
    {
        return $this->currentListenAction() == $action;
    }

    /**
     * Get the underlying listen collection.
     *
     * @return \LaraGram\Listening\ListenCollectionInterface
     */
    public function getListens()
    {
        return $this->listens;
    }

    /**
     * Set the listen collection instance.
     *
     * @param  \LaraGram\Listening\ListenCollection  $listens
     * @return void
     */
    public function setListens(ListenCollection $listens)
    {
        foreach ($listens as $listen) {
            $listen->setListener($this)->setContainer($this->container);
        }

        $this->listens = $listens;

        $this->container->instance('listens', $this->listens);
    }

    /**
     * Set the compiled listen collection instance.
     *
     * @param  array  $listens
     * @return void
     */
    public function setCompiledListens(array $listens)
    {
        $this->listens = (new CompiledListenCollection($listens['compiled'], $listens['attributes']))
            ->setListener($this)
            ->setContainer($this->container);

        $this->container->instance('listens', $this->listens);
    }

    /**
     * Remove any duplicate middleware from the given array.
     *
     * @param  array  $middleware
     * @return array
     */
    public static function uniqueMiddleware(array $middleware)
    {
        $seen = [];
        $result = [];

        foreach ($middleware as $value) {
            $key = \is_object($value) ? \spl_object_id($value) : $value;

            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $result[] = $value;
            }
        }

        return $result;
    }

    /**
     * Set the container instance used by the listener.
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
     * Dynamically handle calls into the listener instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        if ($method === 'middleware') {
            return (new ListenRegistrar($this))->attribute($method, is_array($parameters[0]) ? $parameters[0] : $parameters);
        }

        if ($method !== 'where' && Str::startsWith($method, 'where')) {
            return (new ListenRegistrar($this))->{$method}(...$parameters);
        }

        return (new ListenRegistrar($this))->attribute($method, array_key_exists(0, $parameters) ? $parameters[0] : true);
    }
}
