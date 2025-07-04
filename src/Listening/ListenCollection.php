<?php

namespace LaraGram\Listening;

use LaraGram\Container\Container;
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

        foreach ($methods as $method) {
            $this->listens[$method][$pattern] = $listen;
        }

        $this->allListens[implode('|', $methods).$pattern] = $listen;
    }

    /**
     * Add the listen to any look-up tables if necessary.
     *
     * @param  \LaraGram\Listening\Listen  $listen
     * @return void
     */
    protected function addLookups($listen)
    {
        // If the listen has a name, we will add it to the name look-up table, so that we
        // will quickly be able to find the listen associated with a name and not have
        // to iterate through every listen every time we need to find a named listen.
        if ($name = $listen->getName()) {
            $this->nameList[$name] = $listen;
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
            if ($listen->getName()) {
                $this->nameList[$listen->getName()] = $listen;
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
     * @param  \LaraGram\Request\Request  $request
     * @return \LaraGram\Listening\Listen
     */
    public function match(Request $request)
    {
        $listens = $this->get($request->method());

        // First, we will see if we can find a matching listen for this current request
        // method. If we can, great, we can just return it so that it can be called
        // by the consumer. Otherwise we will check for listens with another verb.
        $listen = $this->matchAgainstListens($listens, $request);

        return $this->handleMatchedListen($request, $listen);
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
     * Convert the collection to a Symfony ListenCollection instance.
     *
     * @return \LaraGram\Listening\BaseListenCollection
     */
    public function toSymfonyListenCollection()
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
