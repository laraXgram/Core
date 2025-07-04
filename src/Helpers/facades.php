<?php

if (!function_exists('auth')) {
    function auth(): LaraGram\Auth0\Auth
    {
        return app()->make('auth');
    }
}

if (!function_exists('role')) {
    function role(): LaraGram\Auth0\Role
    {
        return app()->make('auth.role');
    }
}

if (!function_exists('level')) {
    function level(): LaraGram\Auth0\Level
    {
        return app()->make('auth.level');
    }
}

if (!function_exists('bot')) {
    function bot(): LaraGram\Listening\Listener
    {
        return app()->make('listener');
    }
}

if (!function_exists('keyboard')) {
    function keyboard(): LaraGram\Keyboard\Keyboard
    {
        return app()->make('keyboard');
    }
}

if (!function_exists('request')) {
    function request(): LaraGram\Laraquest\Laraquest
    {
        return app()->make('laraquest');
    }
}

if (!function_exists('schema')) {
    function schema(): LaraGram\Database\Migrations\Schema
    {
        return app()->make('db.schema');
    }
}

if (!function_exists('cache')) {
    function cache(): LaraGram\Cache\CacheManager
    {
        return app()->make('cache.manager');
    }
}