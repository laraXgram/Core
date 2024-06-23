<?php

use LaraGram\Keyboard\Make;
use LaraGram\Support\Facades\Keyboard;

if (!function_exists('inlineKeyboardMarkup')) {
    function inlineKeyboardMarkup(...$row): \LaraGram\Keyboard\Keyboard
    {
        return Keyboard::inlineKeyboardMarkup($row);
    }
}

if (!function_exists('replyKeyboardMarkup')) {
    function replyKeyboardMarkup(...$row): \LaraGram\Keyboard\Keyboard
    {
        return Keyboard::replyKeyboardMarkup($row);
    }
}

if (!function_exists('col')) {
    function col($text, $url = '', $callback_data = '', $web_app = '', $login_url = '', $switch_inline_query = null, $switch_inline_query_current_chat = null, $switch_inline_query_chosen_chat = null, $callback_game = '', $pay = '', $request_user = '', $request_chat = '', $request_contact = '', $request_location = '', $request_poll = ''): array
    {
        return Make::col($text, $url, $callback_data, $web_app, $login_url, $switch_inline_query, $switch_inline_query_current_chat, $switch_inline_query_chosen_chat, $callback_game, $pay, $request_user, $request_chat, $request_contact, $request_location, $request_poll);
    }
}

if (!function_exists('row')) {
    function row(...$col): array
    {
        return Make::row($col);
    }
}