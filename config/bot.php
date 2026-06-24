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

    'default' => env("BOT_CONNECTION", 'bot'),

    'connections' => [
        'bot' => [
            'token' => env("BOT_TOKEN"),
            'url' => env("BOT_URL"),
            'username' => env("BOT_USERNAME", ''),
            'userid' => env("BOT_USERID", ''),
            'secret_token' => null,
            'allowed_updates' => ['*']
        ],
    ],

    'api_server' => [
        'endpoint' => env("API_ENDPOINT", "https://api.telegram.org"),
        'dir' => env("API_DIR", storage_path('app/api-server')),
        'log_dir' => '',
        'ip' => '127.0.0.1',
        'port' => 8081,
        'stat' => [
            'ip' => '',
            'port' => ''
        ],
        'api_id' => env("API_ID", ''),
        'api_hash' => env("API_HASH", ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Smart Anti-Flood
    |--------------------------------------------------------------------------
    |
    | Automatically paces every Telegram API call so the bot never trips
    | Telegram's flood limits — you never add sleeps by hand. Naturally spaced
    | calls and short bursts pay zero delay; only a sustained loop (bulk send,
    | mass delete, kick, broadcast) is slowed, exactly when a flood would
    | otherwise happen. Each limit allows a `burst` of instant calls, then
    | paces to `rate` per `per` seconds.
    */

    'anti_flood' => [

        'enabled' => env('ANTI_FLOOD', false),

        'store' => env('ANTI_FLOOD_STORE', 'redis'),

        'global' => [
            'rate' => 30,
            'per' => 1,
            'burst' => 30,
        ],

        'chat' => [
            'private' => [
                'rate' => 1,
                'per' => 1,
                'burst' => 5,
            ],
            'group' => [
                'rate' => 20,
                'per' => 60,
                'burst' => 5,
            ],
        ],

        'custom' => [
            'broadcast' => [
                'rate' => 4,
                'per' => 1,
                'every' => 100,
                'pause' => 10,
                'shared' => false
            ],
        ],

        'reactive' => [
            'enabled' => true,
            'cooldown_margin' => 0.5,
            'default_cooldown' => 1.0,
        ],

        'sleep' => [
            'driver' => 'auto', // auto | usleep | coroutine (need Swoole)
            'max_delay' => 5.0,
        ],
    ],
];
