<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Navigation Store
    |--------------------------------------------------------------------------
    |
    | The cache store each user's trail of steps is kept in. Set to null to use
    | the default cache store, or name any store defined in config/cache.php.
    | Use a store that outlives the request: "array" forgets the trail at once.
    |
    */

    'store' => env('NAVIGATION_STORE'),

    /*
    |--------------------------------------------------------------------------
    | Navigation Cache Prefix
    |--------------------------------------------------------------------------
    |
    | The prefix of the cache keys of the trails.
    |
    */

    'prefix' => 'navigation',

    /*
    |--------------------------------------------------------------------------
    | Navigation Lifetime
    |--------------------------------------------------------------------------
    |
    | The number of seconds a user's trail is remembered after their last step.
    |
    */

    'lifetime' => 604800,

    /*
    |--------------------------------------------------------------------------
    | Navigation Limit
    |--------------------------------------------------------------------------
    |
    | The number of steps remembered per user. Older steps are forgotten first.
    |
    */

    'limit' => 50,

];
