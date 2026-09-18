<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Broadcaster
    |--------------------------------------------------------------------------
    |
    | This option controls the default broadcaster that will be used by the
    | framework when an event needs to be broadcast. You may set this to
    | any of the connections defined in the "connections" array below.
    |
    | Supported: "telegram", "redis", "log", "null"
    |
    */

    'default' => env('BROADCAST_CONNECTION', 'null'),

    /*
    |--------------------------------------------------------------------------
    | Broadcast Connections
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the broadcast connections that will be used
    | to broadcast Bot API calls to Telegram chats, or events over a
    | WebSocket connection. The "telegram" connection is used by the
    | Broadcast facade's audiences, whatever the default is.
    |
    */

    'connections' => [

        'telegram' => [
            'driver' => 'telegram',
            'bot' => env('BROADCAST_BOT'),
            'chunk' => 100,
            'queue_connection' => env('BROADCAST_QUEUE_CONNECTION'),
            'queue' => env('BROADCAST_QUEUE'),
            'anti_flood' => 'broadcast',
            'rate' => 25,
            'retries' => 3,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_BROADCAST_CONNECTION', 'default'),
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Broadcast Store
    |--------------------------------------------------------------------------
    |
    | The store holds the chats your bot can reach, the members seen in them,
    | and what recallable broadcasts sent, so audiences and filters have
    | something to read. You may configure each store's options here.
    |
    | Supported drivers: "database", "redis", "null"
    |
    */

    'store' => env('BROADCAST_STORE', 'database'),

    'stores' => [

        'database' => [
            'driver' => 'database',
            'connection' => env('BROADCAST_STORE_CONNECTION'),
            'chats_table' => 'broadcast_chats',
            'members_table' => 'broadcast_members',
            'targets_table' => 'broadcast_targets',
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('BROADCAST_STORE_REDIS_CONNECTION', 'default'),
            'prefix' => 'broadcast',
        ],

        'null' => [
            'driver' => 'null',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Chat Tracking
    |--------------------------------------------------------------------------
    |
    | The TrackChats middleware records the chats of incoming updates, and the
    | members it sees in groups, writing each of them at most once every
    | "touch_every" seconds.
    |
    */

    'tracking' => [
        'members' => true,
        'touch_every' => 3600,
    ],

    /*
    |--------------------------------------------------------------------------
    | Recallable Broadcasts
    |--------------------------------------------------------------------------
    |
    | A recallable broadcast remembers what it did, so it may be edited later
    | with Broadcast::sent() or undone with Broadcast::recall(). Enable it
    | here for every broadcast, or call recallable() on a single one.
    |
    */

    'recall' => [
        'enabled' => env('BROADCAST_RECALL', false),
        'ttl' => 2592000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Broadcast Progress
    |--------------------------------------------------------------------------
    |
    | Broadcasts record their progress in the cache while they run, so it can
    | be inspected and cancelled. Use a cache store that is shared by every
    | queue worker, such as "redis" or "database".
    |
    */

    'progress' => [
        'store' => env('BROADCAST_PROGRESS_STORE'),
        'ttl' => 604800,
    ],

];
