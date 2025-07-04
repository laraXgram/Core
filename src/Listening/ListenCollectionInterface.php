<?php

namespace LaraGram\Listening;

use LaraGram\Request\Request;

interface ListenCollectionInterface
{
    /**
     * Add a Listen instance to the collection.
     *
     * @param  \LaraGram\Listening\Listen  $listen
     * @return \LaraGram\Listening\Listen
     */
    public function add(Listen $listen);

    /**
     * Refresh the name look-up table.
     *
     * This is done in case any names are fluently defined or if listens are overwritten.
     *
     * @return void
     */
    public function refreshNameLookups();

    /**
     * Refresh the action look-up table.
     *
     * This is done in case any actions are overwritten with new controllers.
     *
     * @return void
     */
    public function refreshActionLookups();

    /**
     * Find the first listen matching a given request.
     *
     * @param  \LaraGram\Request\Request  $request
     * @return \LaraGram\Listening\Listen
     */
    public function match(Request $request);

    /**
     * Get listens from the collection by method.
     *
     * @param  string|null  $method
     * @return \LaraGram\Listening\Listen[]
     */
    public function get($method = null);

    /**
     * Determine if the listen collection contains a given named listen.
     *
     * @param  string  $name
     * @return bool
     */
    public function hasNamedListen($name);

    /**
     * Get a listen instance by its name.
     *
     * @param  string  $name
     * @return \LaraGram\Listening\Listen|null
     */
    public function getByName($name);

    /**
     * Get a listen instance by its controller action.
     *
     * @param  string  $action
     * @return \LaraGram\Listening\Listen|null
     */
    public function getByAction($action);

    /**
     * Get all of the listens in the collection.
     *
     * @return \LaraGram\Listening\Listen[]
     */
    public function getListens();

    /**
     * Get all of the listens keyed by their HTTP verb / method.
     *
     * @return array
     */
    public function getListensByMethod();

    /**
     * Get all of the listens keyed by their name.
     *
     * @return \LaraGram\Listening\Listen[]
     */
    public function getListensByName();
}
