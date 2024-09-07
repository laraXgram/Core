<?php

namespace LaraGram\Cache\Database;

use Illuminate\Database\Eloquent\Model;

class Cache extends Model
{
    protected $fillable = [
        'cache_key',
        'cache_value',
        'expiry_time'
    ];
}