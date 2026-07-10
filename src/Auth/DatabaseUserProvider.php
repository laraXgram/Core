<?php

namespace LaraGram\Auth;

use Closure;
use LaraGram\Contracts\Auth\StatefulAuthenticatable as UserContract;
use LaraGram\Contracts\Auth\UserProvider;
use LaraGram\Contracts\Hashing\Hasher as HasherContract;
use LaraGram\Contracts\Support\Arrayable;
use LaraGram\Database\ConnectionInterface;

class DatabaseUserProvider implements UserProvider
{
    /**
     * The active database connection.
     *
     * @var \LaraGram\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * The hasher implementation.
     *
     * @var \LaraGram\Contracts\Hashing\Hasher
     */
    protected $hasher;

    /**
     * The table containing the users.
     *
     * @var string
     */
    protected $table;

    /**
     * The column containing the Telegram user id (bot guard).
     *
     * @var string|null
     */
    protected $column;

    /**
     * Create a new database user provider.
     *
     * @param  \LaraGram\Database\ConnectionInterface  $connection
     * @param  \LaraGram\Contracts\Hashing\Hasher  $hasher
     * @param  string  $table
     * @param  string|null  $column
     */
    public function __construct(ConnectionInterface $connection, HasherContract $hasher, $table, $column = null)
    {
        $this->connection = $connection;
        $this->table = $table;
        $this->hasher = $hasher;
        $this->column = $column;
    }

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed  $identifier
     * @return \LaraGram\Contracts\Auth\StatefulAuthenticatable|null
     */
    public function retrieveById($identifier)
    {
        $user = $this->connection->table($this->table)->find($identifier);

        return $this->getGenericUser($user);
    }

    /**
     * Retrieve a user by the Telegram user identifier (bot guard).
     *
     * @param  mixed  $identifier
     * @return \LaraGram\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByUserId($identifier)
    {
        $user = $this->connection->table($this->table)->find($identifier, $this->column);

        return $this->getGenericUser($user, $this->column ?? 'id');
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param  mixed  $identifier
     * @param  string  $token
     * @return \LaraGram\Contracts\Auth\StatefulAuthenticatable|null
     */
    public function retrieveByToken($identifier, #[\SensitiveParameter] $token)
    {
        $user = $this->getGenericUser(
            $this->connection->table($this->table)->find($identifier)
        );

        return $user && $user->getRememberToken() && hash_equals($user->getRememberToken(), $token)
            ? $user
            : null;
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param  \LaraGram\Contracts\Auth\StatefulAuthenticatable  $user
     * @param  string  $token
     * @return void
     */
    public function updateRememberToken(UserContract $user, #[\SensitiveParameter] $token)
    {
        $this->connection->table($this->table)
            ->where($user->getAuthIdentifierName(), $user->getAuthIdentifier())
            ->update([$user->getRememberTokenName() => $token]);
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     * @return \LaraGram\Contracts\Auth\StatefulAuthenticatable|null
     */
    public function retrieveByCredentials(#[\SensitiveParameter] array $credentials)
    {
        $credentials = array_filter(
            $credentials,
            fn ($key) => ! str_contains($key, 'password'),
            ARRAY_FILTER_USE_KEY
        );

        if (empty($credentials)) {
            return;
        }

        // First we will add each credential element to the query as a where clause.
        // Then we can execute the query and, if we found a user, return it in a
        // generic "user" object that will be utilized by the Guard instances.
        $query = $this->connection->table($this->table);

        foreach ($credentials as $key => $value) {
            if (is_array($value) || $value instanceof Arrayable) {
                $query->whereIn($key, $value);
            } elseif ($value instanceof Closure) {
                $value($query);
            } else {
                $query->where($key, $value);
            }
        }

        // Now we are ready to execute the query to see if we have a user matching
        // the given credentials. If not, we will just return null and indicate
        // that there are no matching users from the given credential arrays.
        $user = $query->first();

        return $this->getGenericUser($user);
    }

    /**
     * Get the generic user.
     *
     * @param  mixed  $user
     * @param  string  $authIdentifierName
     * @return \LaraGram\Auth\GenericUser|null
     */
    protected function getGenericUser($user, $authIdentifierName = 'id')
    {
        if (! is_null($user)) {
            return new GenericUser((array) $user, $authIdentifierName);
        }
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param  \LaraGram\Contracts\Auth\StatefulAuthenticatable  $user
     * @param  array  $credentials
     * @return bool
     */
    public function validateCredentials(UserContract $user, #[\SensitiveParameter] array $credentials)
    {
        if (is_null($plain = $credentials['password'])) {
            return false;
        }

        if (is_null($hashed = $user->getAuthPassword())) {
            return false;
        }

        return $this->hasher->check($plain, $hashed);
    }

    /**
     * Rehash the user's password if required and supported.
     *
     * @param  \LaraGram\Contracts\Auth\StatefulAuthenticatable  $user
     * @param  array  $credentials
     * @param  bool  $force
     * @return void
     */
    public function rehashPasswordIfRequired(UserContract $user, #[\SensitiveParameter] array $credentials, bool $force = false)
    {
        if (! $this->hasher->needsRehash($user->getAuthPassword()) && ! $force) {
            return;
        }

        $this->connection->table($this->table)
            ->where($user->getAuthIdentifierName(), $user->getAuthIdentifier())
            ->update([$user->getAuthPasswordName() => $this->hasher->make($credentials['password'])]);
    }
}
