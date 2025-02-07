<?php

namespace LaraGram\Keyboard;

class Make
{
    public static function row(...$col): array
    {
        return [...$col];
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