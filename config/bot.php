<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Bot Connection
    |--------------------------------------------------------------------------
    |
    | The bot connection with which the request is sent by default.
    | Set 'auto' for Multi Bot Update Handling. connection detected by `secret_token`
    | Or set "connection_name" for a specific connection.
    */

    'default' => 'bot',

    'connections' => [
        'bot' => [
            'token' => '',
            'url' => '',
            'username' => '',
            'userid' => '',
            'secret_token' => null,
            'allowed_updates' => ['*']
        ],
    ],

    'api_server' => [
        'endpoint' => 'https://api.telegram.org',
        'dir' => storage_path('app/api-server'),
        'log_dir' => '',
        'ip' => '127.0.0.1',
        'port' => 8081,
        'stat' => [
            'ip' => '',
            'port' => ''
        ],
        'api_id' => '',
        'api_hash' => ''
    ],
];
