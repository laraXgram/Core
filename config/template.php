<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Template Storage Paths
    |--------------------------------------------------------------------------
    |
    | Most templating systems load templates from disk. Here you may specify
    | an array of paths that should be checked for your templates. Of course
    | the usual LaraGram view path has already been registered for you.
    |
    */

    'paths' => [
        app_path('templates'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compiled Templates Path
    |--------------------------------------------------------------------------
    |
    | This option determines where all the compiled Temple8 templates will be
    | stored for your application. Typically, this is within the storage
    | directory. However, as usual, you are free to change this value.
    |
    */

    'compiled' => env(
        'TEMPLATES_COMPILED_PATH',
        realpath(storage_path('framework/templates'))
    ),

];
