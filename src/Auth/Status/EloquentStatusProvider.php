<?php

namespace LaraGram\Auth\Status;

use LaraGram\Contracts\Auth\StatusProvider;

class EloquentStatusProvider implements StatusProvider
{
    /**
     * The Eloquent model class.
     *
     * @var string
     */
    protected $model;

    /**
     * The column holding the status value.
     *
     * @var string
     */
    protected $statusColumn;

    /**
     * The column holding the user id.
     *
     * @var string
     */
    protected $userColumn;

    /**
     * The column holding the chat id.
     *
     * @var string
     */
    protected $chatColumn;

    /**
     * Create a new Eloquent status provider.
     *
     * @param  string  $model
     * @param  string  $statusColumn
     * @param  string  $userColumn
     * @param  string  $chatColumn
     * @return void
     */
    public function __construct($model, $statusColumn = 'status', $userColumn = 'user_id', $chatColumn = 'chat_id')
    {
        $this->model = $model;
        $this->statusColumn = $statusColumn;
        $this->userColumn = $userColumn;
        $this->chatColumn = $chatColumn;
    }

    /**
     * {@inheritdoc}
     */
    public function get($userId, $chatId)
    {
        return $this->newQuery()
            ->where($this->userColumn, $userId)
            ->where($this->chatColumn, $chatId)
            ->value($this->statusColumn);
    }

    /**
     * {@inheritdoc}
     */
    public function put($userId, $chatId, array $attributes)
    {
        $this->newQuery()->updateOrCreate(
            [$this->userColumn => $userId, $this->chatColumn => $chatId],
            $this->mapAttributes($attributes)
        );
    }

    /**
     * Map the normalized "status" key onto the configured status column.
     *
     * @param  array  $attributes
     * @return array
     */
    protected function mapAttributes(array $attributes)
    {
        if (array_key_exists('status', $attributes) && $this->statusColumn !== 'status') {
            $attributes[$this->statusColumn] = $attributes['status'];
            unset($attributes['status']);
        }

        return $attributes;
    }

    /**
     * Get a new query builder for the model.
     *
     * @return \LaraGram\Database\Eloquent\Builder
     */
    protected function newQuery()
    {
        $class = '\\'.ltrim($this->model, '\\');

        return (new $class)->newQuery();
    }
}
