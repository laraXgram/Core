<?php

use LaraGram\Request\Request;

if (!function_exists('chat')) {
    function chat(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('request');
        return match (true) {
            $request->message != null => $request->message->chat,
            $request->edited_message != null => $request->edited_message->chat,
            $request->channel_post != null => $request->channel_post->chat,
            $request->edited_channel_post != null => $request->edited_channel_post->chat,
            $request->business_connection != null => $request->business_connection->user_chat_id,
            $request->business_message != null => $request->business_message->chat,
            $request->edited_business_message != null => $request->edited_business_message->chat,
            $request->deleted_business_messages != null => $request->deleted_business_messages->chat,
            $request->message_reaction != null => $request->message_reaction->chat,
            $request->message_reaction_count != null => $request->message_reaction_count->chat,
            $request->my_chat_member != null => $request->my_chat_member->chat,
            $request->chat_member != null => $request->chat_member->chat,
            $request->chat_join_request != null => $request->chat_join_request->chat,
            $request->chat_boost != null => $request->chat_boost->chat,
            $request->removed_chat_boost != null => $request->removed_chat_boost->chat,
            default => null
        };
    }
}

if (!function_exists('user')) {
    function user(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('request');
        return match (true) {
            $request->message != null => $request->message->from,
            $request->edited_message != null => $request->edited_message->from,
            $request->channel_post != null => $request->channel_post->from,
            $request->edited_channel_post != null => $request->edited_channel_post->from,
            $request->business_connection != null => $request->business_connection->user,
            $request->business_message != null => $request->business_message->from,
            $request->edited_business_message != null => $request->edited_business_message->from,
            $request->message_reaction != null => $request->message_reaction->user,
            $request->inline_query != null => $request->inline_query->from,
            $request->chosen_inline_result != null => $request->chosen_inline_result->from,
            $request->callback_query != null => $request->callback_query->from,
            $request->shipping_query != null => $request->shipping_query->from,
            $request->pre_checkout_query != null => $request->pre_checkout_query->from,
            $request->poll_answer != null => $request->poll_answer->user,
            $request->my_chat_member != null => $request->my_chat_member->from,
            $request->chat_member != null => $request->chat_member->from,
            $request->chat_join_request != null => $request->chat_join_request->from,
            default => null
        };
    }
}

if (!function_exists('update_id')) {
    function update_id(): int|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('request');
        return $request->update_id;
    }
}

if (!function_exists('message')) {
    function message(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('request');
        return $request->message;
    }
}

if (!function_exists('edited_message')) {
    function edited_message(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('request');
        return $request->edited_message;
    }
}

if (!function_exists('channel_post')) {
    function channel_post(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('request');
        return $request->channel_post;
    }
}

if (!function_exists('edited_channel_post')) {
    function edited_channel_post(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('request');
        return $request->edited_channel_post;
    }
}

if (!function_exists('business_connection')) {
    function business_connection(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('request');
        return $request->business_connection;
    }
}

if (!function_exists('business_message')) {
    function business_message(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('request');
        return $request->business_message;
    }
}

if (!function_exists('edited_business_message')) {
    function edited_business_message(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('request');
        return $request->edited_business_message;
    }
}

if (!function_exists('deleted_business_messages')) {
    function deleted_business_messages(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('request');
        return $request->deleted_business_messages;
    }
}

if (!function_exists('message_reaction')) {
    function message_reaction(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('request');
        return $request->message_reaction;
    }
}

if (!function_exists('message_reaction_count')) {
    function message_reaction_count(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('request');
        return $request->message_reaction_count;
    }
}

if (!function_exists('inline_query')) {
    function inline_query(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('request');
        return $request->inline_query;
    }
}

if (!function_exists('chosen_inline_result')) {
    function chosen_inline_result(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('request');
        return $request->chosen_inline_result;
    }
}

if (!function_exists('callback_query')) {
    function callback_query(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('request');
        return $request->callback_query;
    }
}

if (!function_exists('shipping_query')) {
    function shipping_query(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('request');
        return $request->shipping_query;
    }
}

if (!function_exists('pre_checkout_query')) {
    function pre_checkout_query(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('request');
        return $request->pre_checkout_query;
    }
}

if (!function_exists('poll')) {
    function poll(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('request');
        return $request->poll;
    }
}

if (!function_exists('poll_answer')) {
    function poll_answer(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('request');
        return $request->poll_answer;
    }
}

if (!function_exists('my_chat_member')) {
    function my_chat_member(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('request');
        return $request->my_chat_member;
    }
}

if (!function_exists('chat_member')) {
    function chat_member(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('request');
        return $request->chat_member;
    }
}

if (!function_exists('chat_join_request')) {
    function chat_join_request(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('request');
        return $request->chat_join_request;
    }
}

if (!function_exists('chat_join_request')) {
    function chat_join_request(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('request');
        return $request->chat_join_request;
    }
}

if (!function_exists('chat_boost')) {
    function chat_boost(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('request');
        return $request->chat_boost;
    }
}

if (!function_exists('removed_chat_boost')) {
    function removed_chat_boost(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('request');
        return $request->removed_chat_boost;
    }
}