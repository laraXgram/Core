<?php

use LaraGram\Keyboard\Make;
use LaraGram\Support\Facades\Keyboard;

if (!function_exists('inlineKeyboardMarkup')) {
    function inlineKeyboardMarkup(...$row): \LaraGram\Keyboard\Keyboard
    {
        return Keyboard::inlineKeyboardMarkup(...$row);
    }
}

if (!function_exists('replyKeyboardMarkup')) {
    function replyKeyboardMarkup(...$row): \LaraGram\Keyboard\Keyboard
    {
        return Keyboard::replyKeyboardMarkup(...$row);
    }
}

if (!function_exists('replyKeyboardRemove')) {
    function replyKeyboardRemove($selective = false): \LaraGram\Keyboard\Keyboard
    {
        return Keyboard::replyKeyboardRemove($selective);
    }
}

if (!function_exists('forceReply')) {
    function forceReply($input_field_placeholder = '', $selective = false): \LaraGram\Keyboard\Keyboard
    {
        return Keyboard::forceReply($input_field_placeholder, $selective);
    }
}

if (!function_exists('copyTextButton')) {
    function copyTextButton(string $text): \LaraGram\Keyboard\Keyboard
    {
        return Keyboard::copyTextButton($text);
    }
}

if (!function_exists('col')) {
    function col($text, $url = null, $callback_data = null, $web_app = null, $login_url = null, $switch_inline_query = null, $switch_inline_query_current_chat = null, $switch_inline_query_chosen_chat = null, $callback_game = null, $pay = null, $request_user = null, $request_chat = null, $request_contact = null, $request_location = null, $request_poll = null): array
    {
        return Make::col($text, $url, $callback_data, $web_app, $login_url, $switch_inline_query, $switch_inline_query_current_chat, $switch_inline_query_chosen_chat, $callback_game, $pay, $request_user, $request_chat, $request_contact, $request_location, $request_poll);
    }
}

if (!function_exists('row')) {
    function row(...$col): array
    {
        return Make::row(...$col);
    }
}