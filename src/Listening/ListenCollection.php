<?php

namespace LaraGram\Listening;

use LaraGram\Container\Container;
use LaraGram\Listening\Contracts\ProvidesListenContext;
use LaraGram\Request\Request;
use LaraGram\Support\Arr;

class ListenCollection extends AbstractListenCollection
{
    /**
     * An array of the listens keyed by method.
     *
     * @var array
     */
    protected $listens = [];

    /**
     * A flattened array of all of the listens.
     *
     * @var \LaraGram\Listening\Listen[]
     */
    protected $allListens = [];

    /**
     * A look-up table of listens by their names.
     *
     * @var \LaraGram\Listening\Listen[]
     */
    protected $nameList = [];

    /**
     * A look-up table of listens by controller action.
     *
     * @var \LaraGram\Listening\Listen[]
     */
    protected $actionList = [];

    /**
     * Add a Listen instance to the collection.
     *
     * @param  \LaraGram\Listening\Listen  $listen
     * @return \LaraGram\Listening\Listen
     */
    public function add(Listen $listen)
    {
        $this->addToCollections($listen);

        $this->addLookups($listen);

        return $listen;
    }

    /**
     * Add the given listen to the arrays of listens.
     *
     * @param  \LaraGram\Listening\Listen  $listen
     * @return void
     */
    protected function addToCollections($listen)
    {
        $methods = $listen->methods();
        $pattern = $listen->pattern();
        $connections = $listen->getForConnections();
        $collectionKey = (count($connections) === 1 && $connections[0] === '*')
            ? $pattern
            : $pattern . '@' . implode(',', $connections);

        foreach ($methods as $method) {
            $this->listens[$method][$collectionKey] = $listen;
        }

        $this->allListens[implode('|', $methods) . $collectionKey] = $listen;
    }

    /**
     * Add the listen to any look-up tables if necessary.
     *
     * @param  \LaraGram\Listening\Listen  $listen
     * @return void
     */
    protected function nameListKey(string $name, Listen $listen): array
    {
        $connections = $listen->getForConnections();

        if (count($connections) === 1 && $connections[0] === '*') {
            return [$name];
        }

        return array_map(fn($c) => $name . '@' . $c, $connections);
    }

    protected function addLookups($listen)
    {
        // If the listen has a name, we will add it to the name look-up table, so that we
        // will quickly be able to find the listen associated with a name and not have
        // to iterate through every listen every time we need to find a named listen.
        if ($name = $listen->getName()) {
            foreach ($this->nameListKey($name, $listen) as $key) {
                $this->nameList[$key] = $listen;
            }
        }

        // When the listen is listening to a controller we will also store the action that
        // is used by the listen. This will let us reverse listen to controllers while
        // processing a request and easily generate URLs to the given controllers.
        $action = $listen->getAction();

        if (isset($action['controller'])) {
            $this->addToActionList($action, $listen);
        }
    }

    /**
     * Add a listen to the controller action dictionary.
     *
     * @param  array  $action
     * @param  \LaraGram\Listening\Listen  $listen
     * @return void
     */
    protected function addToActionList($action, $listen)
    {
        $this->actionList[trim($action['controller'], '\\')] = $listen;
    }

    /**
     * Refresh the name look-up table.
     *
     * This is done in case any names are fluently defined or if listens are overwritten.
     *
     * @return void
     */
    public function refreshNameLookups()
    {
        $this->nameList = [];

        foreach ($this->allListens as $listen) {
            if ($name = $listen->getName()) {
                foreach ($this->nameListKey($name, $listen) as $key) {
                    $this->nameList[$key] = $listen;
                }
            }
        }
    }

    /**
     * Refresh the action look-up table.
     *
     * This is done in case any actions are overwritten with new controllers.
     *
     * @return void
     */
    public function refreshActionLookups()
    {
        $this->actionList = [];

        foreach ($this->allListens as $listen) {
            if (isset($listen->getAction()['controller'])) {
                $this->addToActionList($listen->getAction(), $listen);
            }
        }
    }

    /**
     * Find the first listen matching a given request.
     *
     * @param  \LaraGram\Listening\Contracts\ProvidesListenContext  $request
     * @return \LaraGram\Listening\Listen
     */
    public function match(ProvidesListenContext $request)
    {
        $currentConnection = Request::getDefaultConnection();

        if (Listener::$enableStepListensPriorityRegister) {
            // When enabled, step listens are matched in definition order
            // mixed with normal listens - registration order wins.
            $candidates = array_values(array_filter($this->getListens(), function ($l) use ($request, $currentConnection) {
                return (in_array($request->listenVerb(), $l->methods()) || $l->isStepListen())
                    && $this->listenMatchesConnection($l, $currentConnection);
            }));

            $listen = $this->matchAgainstListens($candidates, $request);

            return $this->handleMatchedListen($request, $listen);
        }

        $methodListens = $this->get($request->listenVerb());

        $normalListens = array_values(array_filter(
            $methodListens,
            fn ($l) => ! $l->isStepListen() && ! $l->isFallback
                && $this->listenMatchesConnection($l, $currentConnection)
        ));

        $listen = $this->matchAgainstListens($normalListens, $request);

        if (! is_null($listen)) {
            return $this->handleMatchedListen($request, $listen);
        }

        $stepListens = array_values(array_filter(
            $this->getListens(),
            fn ($l) => $l->isStepListen() && $this->listenMatchesConnection($l, $currentConnection)
        ));

        $listen = $this->matchAgainstListens($stepListens, $request);

        if (! is_null($listen)) {
            return $this->handleMatchedListen($request, $listen);
        }

        $fallbackListens = array_values(array_filter(
            $methodListens,
            fn ($l) => $l->isFallback && $this->listenMatchesConnection($l, $currentConnection)
        ));

        $listen = $this->matchAgainstListens($fallbackListens, $request);

        return $this->handleMatchedListen($request, $listen);
    }

    /**
     * Find overlap-flagged listens that should run alongside the primary.
     *
     * @param  \LaraGram\Request\Request  $request
     * @param  \LaraGram\Listening\Listen  $primary
     * @return \LaraGram\Listening\Listen[]
     */
    public function matchOverlap(Request $request, Listen $primary)
    {
        $currentConnection = Request::getDefaultConnection();

        $candidates = array_values(array_filter(
            $this->get($request->method()),
            fn ($l) => $l->overlap
                && $l !== $primary
                && ! $l->isStepListen()
                && ! $l->isFallback
                && $this->listenMatchesConnection($l, $currentConnection)
                && $l->matches($request)
        ));

        $selected = [];

        // Group-less overlap listens always co-run with the primary.
        foreach ($candidates as $i => $l) {
            if ($l->overlapGroups === []) {
                $selected[] = $l;
                unset($candidates[$i]);
            }
        }
        $candidates = array_values($candidates);

        // Seed the active group set from the primary, then grow transitively.
        $activeGroups = $primary->overlap ? $primary->overlapGroups : [];

        do {
            $added = false;

            foreach ($candidates as $i => $l) {
                if (array_intersect($activeGroups, $l->overlapGroups)) {
                    $selected[] = $l;
                    $activeGroups = array_values(array_unique(
                        array_merge($activeGroups, $l->overlapGroups)
                    ));
                    unset($candidates[$i]);
                    $added = true;
                }
            }
        } while ($added);

        return $selected;
    }

    /**
     * Get listens from the collection by method.
     *
     * @param  string|null  $method
     * @return \LaraGram\Listening\Listen[]
     */
    public function get($method = null)
    {
        return is_null($method) ? $this->getListens() : Arr::get($this->listens, $method, []);
    }

    /**
     * Determine if the listen collection contains a given named listen.
     *
     * @param  string  $name
     * @return bool
     */
    public function hasNamedListen($name)
    {
        return ! is_null($this->getByName($name));
    }

    /**
     * Get a listen instance by its name.
     *
     * @param  string  $name
     * @return \LaraGram\Listening\Listen|null
     */
    public function getByName($name)
    {
        $currentConnection = \LaraGram\Request\Request::getDefaultConnection();

        if ($currentConnection !== null) {
            $connectionKey = $name . '@' . $currentConnection;

            if (isset($this->nameList[$connectionKey])) {
                return $this->nameList[$connectionKey];
            }
        }

        return $this->nameList[$name] ?? null;
    }

    /**
     * Get a listen instance by its controller action.
     *
     * @param  string  $action
     * @return \LaraGram\Listening\Listen|null
     */
    public function getByAction($action)
    {
        return $this->actionList[$action] ?? null;
    }

    /**
     * Get all of the listens in the collection.
     *
     * @return \LaraGram\Listening\Listen[]
     */
    public function getListens()
    {
        return array_values($this->allListens);
    }

    /**
     * Get all of the listens keyed by their HTTP verb / method.
     *
     * @return array
     */
    public function getListensByMethod()
    {
        return $this->listens;
    }

    /**
     * Get all of the listens keyed by their name.
     *
     * @return \LaraGram\Listening\Listen[]
     */
    public function getListensByName()
    {
        return $this->nameList;
    }

    /**
     * Convert the collection to a BaseListenCollection instance.
     *
     * @return \LaraGram\Listening\BaseListenCollection
     */
    public function toBaseListenCollection()
    {
        $baseListens = parent::toBaseListenCollection();

        $this->refreshNameLookups();

        return $baseListens;
    }

    /**
     * Convert the collection to a CompiledListenCollection instance.
     *
     * @param  \LaraGram\Listening\Listener  $listener
     * @param  \LaraGram\Container\Container  $container
     * @return \LaraGram\Listening\CompiledListenCollection
     */
    public function toCompiledListenCollection(Listener $listener, Container $container)
    {
        ['compiled' => $compiled, 'attributes' => $attributes] = $this->compile();

        return (new CompiledListenCollection($compiled, $attributes))
            ->setListener($listener)
            ->setContainer($container);
    }
}
