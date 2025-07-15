<?php

namespace LaraGram\Listening;

use ArrayIterator;
use Countable;
use LaraGram\Request\Request;
use LaraGram\Request\Response;
use LaraGram\Support\Collection;
use LaraGram\Support\Str;
use IteratorAggregate;
use LogicException;
use Traversable;

abstract class AbstractListenCollection implements Countable, IteratorAggregate, ListenCollectionInterface
{
    /**
     * Handle the matched listen.
     *
     * @param  \LaraGram\Request\Request  $request
     * @param  \LaraGram\Listening\Listen|null  $listen
     * @return \LaraGram\Listening\Listen
     *
     * @throws \Exception
     */
    protected function handleMatchedListen(Request $request, $listen)
    {
        if (! is_null($listen)) {
            return $listen->bind($request);
        }

        // If no listen was found we will now check if a matching listen is specified by
        // another HTTP verb. If it is we will need to throw a MethodNotAllowed and
        // inform the user agent of which HTTP verb it should use for this listen.
        $others = $this->checkForAlternateVerbs($request);

        if (count($others) > 0) {
            return $this->getListenForMethods($request, $others);
        }

        throw new \Exception(sprintf(
            'The listen %s could not be found.',
            $request->method()
        ));
    }

    /**
     * Determine if any listens match on another HTTP verb.
     *
     * @param  \LaraGram\Request\Request  $request
     * @return array
     */
    protected function checkForAlternateVerbs($request)
    {
        $methods = array_diff(Listener::$verbs, [$request->method()]);

        // Here we will spin through all verbs except for the current request verb and
        // check to see if any listens respond to them. If they do, we will return a
        // proper error response with the correct headers on the response string.
        return array_values(array_filter(
            $methods,
            function ($method) use ($request) {
                return ! is_null($this->matchAgainstListens($this->get($method), $request, false));
            }
        ));
    }

    /**
     * Determine if a listen in the array matches the request.
     *
     * @param  \LaraGram\Listening\Listen[]  $listens
     * @param  \LaraGram\Request\Request  $request
     * @param  bool  $includingMethod
     * @return \LaraGram\Listening\Listen|null
     */
    protected function matchAgainstListens(array $listens, $request, $includingMethod = true)
    {
        [$fallbacks, $listens] = (new Collection($listens))->partition(function ($listen) {
            return $listen->isFallback;
        });

        return $listens->merge($fallbacks)->first(
            fn (Listen $listen) => $listen->matches($request, $includingMethod)
        );
    }

    /**
     * Get a listen (if necessary) that responds when other available methods are present.
     *
     * @param  \LaraGram\Request\Request  $request
     * @param  string[]  $methods
     * @return \LaraGram\Listening\Listen
     *
     * @throws \Exception
     */
    protected function getListenForMethods($request, array $methods)
    {
        if ($request->isMethod('TEXT')) {
            return (new Listen('TEXT', text(), function () use ($methods) {
                return new Response('');
            }))->bind($request);
        }

        $this->requestMethodNotAllowed($request, $methods, $request->method());
    }

    /**
     * Throw a method not allowed HTTP exception.
     *
     * @param  \LaraGram\Request\Request  $request
     * @param  array  $others
     * @param  string  $method
     * @return never
     *
     * @throws \Exception
     */
    protected function requestMethodNotAllowed($request, array $others, $method)
    {
        throw new \Exception(
            sprintf(
                'The %s method is not supported for listen %s. Supported methods: %s.',
                $method,
                $request->method(),
                implode(', ', $others)
            )
        );
    }

    /**
     * Throw a method not allowed HTTP exception.
     *
     * @param  array  $others
     * @param  string  $method
     * @return void
     *
     * @deprecated use requestMethodNotAllowed
     *
     * @throws \Exception
     */
    protected function methodNotAllowed(array $others, $method)
    {
        throw new \Exception(
            implode(', ', $others),
            sprintf(
                'The %s method is not supported for this listen. Supported methods: %s.',
                $method,
                implode(', ', $others)
            )
        );
    }

    /**
     * Compile the listens for caching.
     *
     * @return array
     * @throws \Exception
     */
    public function compile()
    {
        $compiled = $this->dumper()->getCompiledListens();

        $attributes = [];

        foreach ($this->getListens() as $listen) {
            $attributes[$listen->getName()] = [
                'methods' => $listen->methods(),
                'pattern' => $listen->pattern(),
                'action' => $listen->getAction(),
                'fallback' => $listen->isFallback,
                'defaults' => $listen->defaults,
                'wheres' => $listen->wheres,
                'bindingFields' => $listen->bindingFields(),
                'lockSeconds' => $listen->locksFor(),
                'waitSeconds' => $listen->waitsFor(),
                'withTrashed' => $listen->allowsTrashedBindings(),
            ];
        }

        return compact('compiled', 'attributes');
    }

    /**
     * Return the CompiledUrlMatcherDumper instance for the listen collection.
     *
     * @return \LaraGram\Listening\CompiledPatternMatcherDumper
     */
    public function dumper()
    {
        return new CompiledPatternMatcherDumper($this->toBaseListenCollection());
    }

    /**
     * Convert the collection to a Base ListenCollection instance.
     *
     * @return BaseListenCollection
     */
    public function toBaseListenCollection()
    {
        $baseListens = new BaseListenCollection;

        $listens = $this->getListens();

        foreach ($listens as $listen) {
            if (! $listen->isFallback) {
                $baseListens = $this->addToBaseListensCollection($baseListens, $listen);
            }
        }

        foreach ($listens as $listen) {
            if ($listen->isFallback) {
                $baseListens = $this->addToBaseListensCollection($baseListens, $listen);
            }
        }

        return $baseListens;
    }

    /**
     * Add a listen to the BaseListenCollection instance.
     *
     * @param  BaseListenCollection  $baseListens
     * @param  \LaraGram\Listening\Listen  $listen
     * @return BaseListenCollection
     *
     * @throws \LogicException
     */
    protected function addToBaseListensCollection(BaseListenCollection $baseListens, Listen $listen)
    {
        $name = $listen->getName();

        if (
            ! is_null($name)
            && str_ends_with($name, '.')
            && ! is_null($baseListens->get($name))
        ) {
            $name = null;
        }

        if (! $name) {
            $listen->name($this->generateListenName());

            $this->add($listen);
        } elseif (! is_null($baseListens->get($name))) {
            throw new LogicException("Unable to prepare listen [{$listen->pattern}] for serialization. Another listen has already been assigned name [{$name}].");
        }

        $baseListens->add($listen->getName(), $listen->toBaseListen());

        return $baseListens;
    }

    /**
     * Get a randomly generated listen name.
     *
     * @return string
     */
    protected function generateListenName()
    {
        return 'generated::'.Str::random();
    }

    /**
     * Get an iterator for the items.
     *
     * @return \ArrayIterator
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->getListens());
    }

    /**
     * Count the number of items in the collection.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->getListens());
    }
}
