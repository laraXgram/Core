<?php

namespace LaraGram\Keyboard;

class Make
{
    public static function row(...$col): array
    {
        return [...$col];
    }

    public static function webApp(
        $text,
        $web_app,
    ): array
    {
        return [
            'text' => $text,
            'web_app' => $web_app,
        ];
    }

    public static function url(
        $text,
        $url
    ): array
    {
        return [
            'text' => $text,
            'url' => $url,
        ];
    }
    public static function callbackData(
        $text,
        $callback_data
    ): array
    {
        return [
            'text' => $text,
            'callback_data' => $callback_data,
        ];
    }

    public static function loginUrl(
        $text,
        $login_url
    ): array
    {
        return [
            'text' => $text,
            'login_url' => $login_url,
        ];
    }

    public static function switchInlineQuery(
        $text,
        $switch_inline_query = null,
        $switch_inline_query_current_chat = null,
        $switch_inline_query_chosen_chat = null
    ): array
    {
        return [
            'text' => $text,
            'switch_inline_query' => $switch_inline_query,
            'switch_inline_query_current_chat' => $switch_inline_query_current_chat,
            'switch_inline_query_chosen_chat' => $switch_inline_query_chosen_chat,
        ];
    }

    public static function callbackGame(
        $text,
        $callback_game = null
    ): array
    {
        return [
            'text' => $text,
            'callback_game' => $callback_game,
        ];
    }

    public static function pay(
        $text,
        $pay = null
    ): array
    {
        return [
            'text' => $text,
            'pay' => $pay,
        ];
    }

    public static function requestUser(
        $text,
        $request_user = null
    ): array
    {
        return [
            'text' => $text,
            'request_user' => $request_user,
        ];
    }

    public static function requestChat(
        $text,
        $request_chat = null
    ): array
    {
        return [
            'text' => $text,
            'request_chat' => $request_chat,
        ];
    }

    public static function requestContact(
        $text,
        $request_contact = null
    ): array
    {
        return [
            'text' => $text,
            'request_contact' => $request_contact,
        ];
    }

    public static function requestLocation(
        $text,
        $request_location = null
    ): array
    {
        return [
            'text' => $text,
            'request_location' => $request_location,
        ];
    }

    public static function requestPoll(
        $text,
        $request_poll = null
    ): array
    {
        return [
            'text' => $text,
            'request_poll' => $request_poll,
        ];
    }
    

    public static function col(
        $text,
        $url = null,
        $callback_data = null,
        $web_app = null,
        $login_url = null,
        $switch_inline_query = null,
        $switch_inline_query_current_chat = null,
        $switch_inline_query_chosen_chat = null,
        $callback_game = null,
        $pay = null,
        $request_user = null,
        $request_chat = null,
        $request_contact = null,
        $request_location = null,
        $request_poll = null
    ): array
    {
        return array_filter(get_defined_vars(),function ($var){
            return !is_null($var);
        });
    }
}