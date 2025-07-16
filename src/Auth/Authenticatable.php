<?php

namespace LaraGram\Auth;

trait Authenticatable
{
    /**
     * The column name of the user_id.
     *
     * @var string
     */
    protected $userId = 'user_id';

    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return $this->getUserIdName();
    }

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        return $this->{$this->getAuthIdentifierName()};
    }

    /**
     * Get the name of the password attribute for the user.
     *
     * @return string
     */
    public function getUserIdName()
    {
        return $this->userId;
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getUserId()
    {
        return $this->{$this->getUserIdName()};
    }
}
