<?php

namespace LaraGram\Auth;

use LaraGram\Contracts\Auth\UserProvider;

class EloquentUserProvider implements UserProvider
{
    /**
     * The Eloquent user model.
     *
     * @var string
     */
    protected $model;

    /**
     * The callback that may modify the user retrieval queries.
     *
     * @var (\Closure(\LaraGram\Database\Eloquent\Builder<*>):mixed)|null
     */
    protected $queryCallback;

    /**
     * Create a new database user provider.
     *
     * @param  string  $model
     * @return void
     */
    public function __construct($model)
    {
        $this->model = $model;
    }

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed  $identifier
     * @return \LaraGram\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByUserId($identifier)
    {
        $model = $this->createModel();

        return $this->newModelQuery($model)
            ->where($model->getAuthIdentifierName(), $identifier)
            ->first();
    }

    /**
     * Get a new query builder for the model instance.
     *
     * @template TModel of \LaraGram\Database\Eloquent\Model
     *
     * @param  TModel|null  $model
     * @return \LaraGram\Database\Eloquent\Builder<TModel>
     */
    protected function newModelQuery($model = null)
    {
        $query = is_null($model)
                ? $this->createModel()->newQuery()
                : $model->newQuery();

        with($query, $this->queryCallback);

        return $query;
    }

    /**
     * Create a new instance of the model.
     *
     * @return \LaraGram\Database\Eloquent\Model
     */
    public function createModel()
    {
        $class = '\\'.ltrim($this->model, '\\');

        return new $class;
    }

    /**
     * Gets the name of the Eloquent user model.
     *
     * @return string
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Sets the name of the Eloquent user model.
     *
     * @param  string  $model
     * @return $this
     */
    public function setModel($model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Get the callback that modifies the query before retrieving users.
     *
     * @return (\Closure(\LaraGram\Database\Eloquent\Builder<*>):mixed)|null
     */
    public function getQueryCallback()
    {
        return $this->queryCallback;
    }

    /**
     * Sets the callback to modify the query before retrieving users.
     *
     * @param  (\Closure(\LaraGram\Database\Eloquent\Builder<*>):mixed)|null  $queryCallback
     * @return $this
     */
    public function withQuery($queryCallback = null)
    {
        $this->queryCallback = $queryCallback;

        return $this;
    }
}
