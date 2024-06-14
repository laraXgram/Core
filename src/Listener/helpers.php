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