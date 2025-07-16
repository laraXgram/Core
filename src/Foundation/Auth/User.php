<?php

namespace LaraGram\Foundation\Auth;

use LaraGram\Auth\Authenticatable;
use LaraGram\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use LaraGram\Contracts\Auth\Authenticatable as AuthenticatableContract;
use LaraGram\Database\Eloquent\Model;
use LaraGram\Foundation\Auth\Access\Authorizable;

class User extends Model implements
    AuthenticatableContract,
    AuthorizableContract
{
    use Authenticatable, Authorizable;
}
