<?php

namespace LaraGram\Events;

use Closure;
use Exception;
use InvalidArgumentException;
use LaraGram\Container\Container;
use LaraGram\Contracts\Container\Container as ContainerContract;
use LaraGram\Contracts\Events\Dispatcher as DispatcherContract;
use LaraGram\Contracts\Events\ShouldDispatchAfterCommit;
use LaraGram\Contracts\Queue\ShouldBeEncrypted;
use LaraGram\Contracts\Queue\ShouldQueue;
use LaraGram\Contracts\Queue\ShouldQueueAfterCommit;
use LaraGram\Support\Arr;
use LaraGram\Support\Traits\Macroable;
use LaraGram\Support\Traits\ReflectsClosures;
use ReflectionClass;
use ReflectionException;

class Dispatcher implements DispatcherContract
{
    use Macroable, ReflectsClosures;

    protected $container;
    protected $listeners = [];
    protected $wildcards = [];
    protected $wildcardsCache = [];
    protected $queueResolver;

    public function __construct(?ContainerContract $container = null)
    {
        $this->container = $container ?: new Container;
    }

    /**
     * @throws ReflectionException
     */
    public function listen($events, $listener = null): void
    {
        if ($events instanceof Closure) {
            $eventTypes = $this->firstClosureParameterTypes($events);

            foreach ($eventTypes as $event) {
                $this->listen($event, $events);
            }

            return;
        } elseif ($events instanceof QueuedClosure) {
            $eventTypes = $this->firstClosureParameterTypes($events->closure);

            foreach ($eventTypes as $event) {
                $this->listen($event, $events->resolve());
            }

            return;
        } elseif ($listener instanceof QueuedClosure) {
            $listener = $listener->resolve();
        }

        foreach ((array) $events as $event) {
            if (str_contains($event, '*')) {
                $this->setupWildcardListen($event, $listener);
            } else {
                $this->listeners[$event][] = $listener;
            }
        }
    }

    protected function setupWildcardListen($event, $listener): void
    {
        $this->wildcards[$event][] = $listener;

        $this->wildcardsCache = [];
    }

    public function hasListeners($eventName): bool
    {
        return isset($this->listeners[$eventName]) ||
            isset($this->wildcards[$eventName]) ||
            $this->hasWildcardListeners($eventName);
    }

    public function hasWildcardListeners($eventName): bool
    {
        foreach ($this->wildcards as $key => $listeners) {
            $pattern = '/^' . str_replace('\*', '.*', preg_quote($key, '/')) . '$/';

            if (preg_match($pattern, $eventName)) {
                return true;
            }
        }

        return false;
    }

    public function push($event, $payload = []): void
    {
        $this->listen($event.'_pushed', function () use ($event, $payload) {
            $this->dispatch($event, $payload);
        });
    }

    public function flush($event): void
    {
        $this->dispatch($event.'_pushed');
    }

    public function subscribe($subscriber)
    {
        $subscriber = $this->resolveSubscriber($subscriber);

        $events = $subscriber->subscribe($this);

        if (is_array($events)) {
            foreach ($events as $event => $listeners) {
                foreach (Arr::wrap($listeners) as $listener) {
                    if (is_string($listener) && method_exists($subscriber, $listener)) {
                        $this->listen($event, [get_class($subscriber), $listener]);

                        continue;
                    }

                    $this->listen($event, $listener);
                }
            }
        }
    }

    protected function resolveSubscriber($subscriber)
    {
        if (is_string($subscriber)) {
            return $this->container->make($subscriber);
        }

        return $subscriber;
    }

    public function until($event, $payload = [])
    {
        return $this->dispatch($event, $payload, true);
    }

    public function dispatch($event, $payload = [], $halt = false)
    {
        [$isEventObject, $event, $payload] = [
            is_object($event),
            ...$this->parseEventAndPayload($event, $payload),
        ];

        if ($isEventObject &&
            $payload[0] instanceof ShouldDispatchAfterCommit &&
            ! is_null($transactions = $this->resolveTransactionManager())) {
            $transactions->addCallback(
                fn () => $this->invokeListeners($event, $payload, $halt)
            );

            return null;
        }

        return $this->invokeListeners($event, $payload, $halt);
    }

    protected function invokeListeners($event, $payload, $halt = false)
    {
        $responses = [];

        foreach ($this->getListeners($event) as $listener) {
            $response = $listener($event, $payload);

            if ($halt && ! is_null($response)) {
                return $response;
            }

            if ($response === false) {
                break;
            }

            $responses[] = $response;
        }

        return $halt ? null : $responses;
    }

    protected function parseEventAndPayload($event, $payload): array
    {
        if (is_object($event)) {
            [$payload, $event] = [[$event], get_class($event)];
        }

        return [$event, Arr::wrap($payload)];
    }

    public function getListeners($eventName)
    {
        $listeners = array_merge(
            $this->prepareListeners($eventName),
            $this->wildcardsCache[$eventName] ?? $this->getWildcardListeners($eventName)
        );

        return class_exists($eventName, false)
            ? $this->addInterfaceListeners($eventName, $listeners)
            : $listeners;
    }

    protected function getWildcardListeners($eventName)
    {
        $wildcards = [];

        foreach ($this->wildcards as $key => $listeners) {
            $pattern = '/^' . str_replace('\*', '.*', preg_quote($key, '/')) . '$/';

            if (preg_match($pattern, $eventName)) {
                foreach ($listeners as $listener) {
                    $wildcards[] = $this->makeListener($listener, true);
                }
            }
        }

        return $this->wildcardsCache[$eventName] = $wildcards;
    }

    protected function addInterfaceListeners($eventName, array $listeners = [])
    {
        foreach (class_implements($eventName) as $interface) {
            if (isset($this->listeners[$interface])) {
                foreach ($this->prepareListeners($interface) as $names) {
                    $listeners = array_merge($listeners, (array) $names);
                }
            }
        }

        return $listeners;
    }

    protected function prepareListeners(string $eventName)
    {
        $listeners = [];

        foreach ($this->listeners[$eventName] ?? [] as $listener) {
            $listeners[] = $this->makeListener($listener);
        }

        return $listeners;
    }

    public function makeListener($listener, $wildcard = false)
    {
        if (is_string($listener)) {
            return $this->createClassListener($listener, $wildcard);
        }

        if (is_array($listener) && isset($listener[0]) && is_string($listener[0])) {
            return $this->createClassListener($listener, $wildcard);
        }

        return function ($event, $payload) use ($listener, $wildcard) {
            if ($wildcard) {
                return $listener($event, $payload);
            }

            return $listener(...array_values($payload));
        };
    }

    public function createClassListener($listener, $wildcard = false)
    {
        return function ($event, $payload) use ($listener, $wildcard) {
            if ($wildcard) {
                return call_user_func($this->createClassCallable($listener), $event, $payload);
            }

            $callable = $this->createClassCallable($listener);

            return $callable(...array_values($payload));
        };
    }

    protected function createClassCallable($listener)
    {
        [$class, $method] = is_array($listener)
            ? $listener
            : $this->parseClassCallable($listener);

        if (! method_exists($class, $method)) {
            $method = '__invoke';
        }

        if ($this->handlerShouldBeQueued($class)) {
            return $this->createQueuedHandlerCallable($class, $method);
        }

        $listener = $this->container->make($class);

        return [$listener, $method];
    }

    protected function parseClassCallable($listener)
    {
        if (is_string($listener)) {
            if (str_contains($listener, '@')) {
                return explode('@', $listener);
            }

            return [$listener, 'handle'];
        }

        if (is_object($listener)) {
            return [get_class($listener), 'handle'];
        }

        throw new InvalidArgumentException('Listener must be a string or an object.');
    }

    protected function handlerShouldBeQueued($class)
    {
        try {
            return (new ReflectionClass($class))->implementsInterface(
                ShouldQueue::class
            );
        } catch (Exception) {
            return false;
        }
    }

    protected function createQueuedHandlerCallable($class, $method)
    {
        return function () use ($class, $method) {
            $arguments = array_map(function ($a) {
                return is_object($a) ? clone $a : $a;
            }, func_get_args());

            if ($this->handlerWantsToBeQueued($class, $arguments)) {
                $this->queueHandler($class, $method, $arguments);
            }
        };
    }

    protected function handlerWantsToBeQueued($class, $arguments)
    {
        $instance = $this->container->make($class);

        if (method_exists($instance, 'shouldQueue')) {
            return $instance->shouldQueue($arguments[0]);
        }

        return true;
    }

    protected function queueHandler($class, $method, $arguments)
    {
        [$listener, $job] = $this->createListenerAndJob($class, $method, $arguments);

        $connection = $this->resolveQueue()->connection(method_exists($listener, 'viaConnection')
            ? (isset($arguments[0]) ? $listener->viaConnection($arguments[0]) : $listener->viaConnection())
            : $listener->connection ?? null);

        $queue = method_exists($listener, 'viaQueue')
            ? (isset($arguments[0]) ? $listener->viaQueue($arguments[0]) : $listener->viaQueue())
            : $listener->queue ?? null;

        $delay = method_exists($listener, 'withDelay')
            ? (isset($arguments[0]) ? $listener->withDelay($arguments[0]) : $listener->withDelay())
            : $listener->delay ?? null;

        is_null($delay)
            ? $connection->pushOn($queue, $job)
            : $connection->laterOn($queue, $delay, $job);
    }

    protected function createListenerAndJob($class, $method, $arguments)
    {
        $listener = (new ReflectionClass($class))->newInstanceWithoutConstructor();

        return [$listener, $this->propagateListenerOptions(
            $listener, new CallQueuedListener($class, $method, $arguments)
        )];
    }

    protected function propagateListenerOptions($listener, $job)
    {
        return tap($job, function ($job) use ($listener) {
            $data = array_values($job->data);

            if ($listener instanceof ShouldQueueAfterCommit) {
                $job->afterCommit = true;
            } else {
                $job->afterCommit = property_exists($listener, 'afterCommit') ? $listener->afterCommit : null;
            }

            $job->backoff = method_exists($listener, 'backoff') ? $listener->backoff(...$data) : ($listener->backoff ?? null);
            $job->maxExceptions = $listener->maxExceptions ?? null;
            $job->retryUntil = method_exists($listener, 'retryUntil') ? $listener->retryUntil(...$data) : null;
            $job->shouldBeEncrypted = $listener instanceof ShouldBeEncrypted;
            $job->timeout = $listener->timeout ?? null;
            $job->failOnTimeout = $listener->failOnTimeout ?? false;
            $job->tries = $listener->tries ?? null;

            $job->through(array_merge(
                method_exists($listener, 'middleware') ? $listener->middleware(...$data) : [],
                $listener->middleware ?? []
            ));
        });
    }

    public function forget($event)
    {
        if (str_contains($event, '*')) {
            unset($this->wildcards[$event]);
        } else {
            unset($this->listeners[$event]);
        }

        foreach ($this->wildcardsCache as $key => $listeners) {
            $pattern = '/^' . str_replace('\*', '.*', preg_quote($key, '/')) . '$/';

            if (preg_match($pattern, $event)) {
                unset($this->wildcardsCache[$key]);
            }
        }
    }

    public function forgetPushed()
    {
        foreach ($this->listeners as $key => $value) {
            if (str_ends_with($key, '_pushed')) {
                $this->forget($key);
            }
        }
    }

    protected function resolveQueue()
    {
        return call_user_func($this->queueResolver);
    }

    public function setQueueResolver(callable $resolver)
    {
        $this->queueResolver = $resolver;

        return $this;
    }

    public function getRawListeners()
    {
        return $this->listeners;
    }
}
