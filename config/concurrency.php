<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Concurrency Driver
    |--------------------------------------------------------------------------
    |
    | This option determines the default concurrency driver that will be used
    | by LaraGram's concurrency functions. By default, concurrent work will
    | be sent to isolated PHP processes which will return their results.
    |
    | Supported: "process", "sync"
    |
    */

    'default' => env('CONCURRENCY_DRIVER', 'process'),

];