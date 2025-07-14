<?php

use LaraGram\Request\Request;

if (!function_exists('chat')) {
    function chat(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('laraquest');
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
        $request = app('laraquest');
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

if (!function_exists('text')) {
    function text(): string|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('laraquest');
        return match (true) {
            $request->message != null && isset($request->message->text) => $request->message->text,
            $request->edited_message != null => $request->edited_message->text,
            $request->channel_post != null => $request->channel_post->text,
            $request->edited_channel_post != null => $request->edited_channel_post->text,
            $request->business_message != null => $request->business_message->text,
            $request->edited_business_message != null => $request->edited_business_message->text,
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
        $request = app('laraquest');
        return $request->update_id;
    }
}

if (!function_exists('message')) {
    function message(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('laraquest');
        return $request->message;
    }
}

if (!function_exists('edited_message')) {
    function edited_message(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('laraquest');
        return $request->edited_message;
    }
}

if (!function_exists('channel_post')) {
    function channel_post(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('laraquest');
        return $request->channel_post;
    }
}

if (!function_exists('edited_channel_post')) {
    function edited_channel_post(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('laraquest');
        return $request->edited_channel_post;
    }
}

if (!function_exists('business_connection')) {
    function business_connection(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('laraquest');
        return $request->business_connection;
    }
}

if (!function_exists('business_message')) {
    function business_message(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('laraquest');
        return $request->business_message;
    }
}

if (!function_exists('edited_business_message')) {
    function edited_business_message(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('laraquest');
        return $request->edited_business_message;
    }
}

if (!function_exists('deleted_business_messages')) {
    function deleted_business_messages(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('laraquest');
        return $request->deleted_business_messages;
    }
}

if (!function_exists('message_reaction')) {
    function message_reaction(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('laraquest');
        return $request->message_reaction;
    }
}

if (!function_exists('message_reaction_count')) {
    function message_reaction_count(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('laraquest');
        return $request->message_reaction_count;
    }
}

if (!function_exists('inline_query')) {
    function inline_query(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('laraquest');
        return $request->inline_query;
    }
}

if (!function_exists('chosen_inline_result')) {
    function chosen_inline_result(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('laraquest');
        return $request->chosen_inline_result;
    }
}

if (!function_exists('callback_query')) {
    function callback_query(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('laraquest');
        return $request->callback_query;
    }
}

if (!function_exists('shipping_query')) {
    function shipping_query(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('laraquest');
        return $request->shipping_query;
    }
}

if (!function_exists('pre_checkout_query')) {
    function pre_checkout_query(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('laraquest');
        return $request->pre_checkout_query;
    }
}

if (!function_exists('poll')) {
    function poll(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('laraquest');
        return $request->poll;
    }
}

if (!function_exists('poll_answer')) {
    function poll_answer(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('laraquest');
        return $request->poll_answer;
    }
}

if (!function_exists('my_chat_member')) {
    function my_chat_member(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('laraquest');
        return $request->my_chat_member;
    }
}

if (!function_exists('chat_member')) {
    function chat_member(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('laraquest');
        return $request->chat_member;
    }
}

if (!function_exists('chat_join_request')) {
    function chat_join_request(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('laraquest');
        return $request->chat_join_request;
    }
}

if (!function_exists('chat_join_request')) {
    function chat_join_request(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('laraquest');
        return $request->chat_join_request;
    }
}

if (!function_exists('chat_boost')) {
    function chat_boost(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('laraquest');
        return $request->chat_boost;
    }
}

if (!function_exists('removed_chat_boost')) {
    function removed_chat_boost(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('laraquest');
        return $request->removed_chat_boost;
    }
}

if (!function_exists('id')) {
    function id()
    {
        return match (true) {
            isset(user()->id) => user()->id,
            isset(chat()->id) => chat()->id,
            default => null
        };
    }
}

if (!function_exists('inline_url')) {
    function inline_url($text, $url, $parse_mode = 'markdown'): false|string
    {
        return match (strtolower($parse_mode)){
            'markdown' => "[{$text}]({$url})",
            'html' => "<a href='{$url}'>{$text}</a>",
            default => false
        };
    }
}

if (!function_exists('code')) {
    function code($text, $parse_mode = 'markdown'): false|string
    {
        return match (strtolower($parse_mode)){
            'markdown' => "`{$text}`",
            'html' => "<code>{$text}</code>",
            default => false
        };
    }
}

if (!function_exists('pre')) {
    function pre($text, $lang = '', $parse_mode = 'markdown'): false|string
    {
        return match (strtolower($parse_mode)){
            'markdown' => "```{$lang}
{$text}
```",
            'html' => "<pre lang='{$lang}'>{$text}</pre>",
            default => false
        };
    }
}

if (!function_exists('spoiler')) {
    function spoiler($text): string
    {
        return "||{$text}||";
    }
}

if (!function_exists('spoiler')) {
    function spoiler($text, $parse_mode = 'markdown'): false|string
    {
        return match (strtolower($parse_mode)){
            'markdown' => "~~{$text}~~",
            'html' => "<s>{$text}</s>",
            default => false
        };
    }
}

if (!function_exists('underline')) {
    function underline($text): string
    {
        return "<u>{$text}</u>";
    }
}

if (!function_exists('italic')) {
    function italic($text, $parse_mode = 'markdown'): false|string
    {
        return match (strtolower($parse_mode)){
            'markdown' => "__{$text}__",
            'html' => "<i>{$text}</i>",
            default => false
        };
    }
}

if (!function_exists('bold')) {
    function bold($text, $parse_mode = 'markdown'): false|string
    {
        return match (strtolower($parse_mode)){
            'markdown' => "**{$text}**",
            'html' => "<b>{$text}</b>",
            default => false
        };
    }
}

if (!function_exists('mention_user_by_ID')) {
    function mention_user_by_ID($user_id, $text, $parse_mode = 'markdown'): false|string
    {
        return match (strtolower($parse_mode)){
            'markdown' => "[{$text}](tg://user?id={$user_id})",
            'html' => "<a href='tg://user?id={$user_id}'>{$text}</a>",
            default => false
        };
    }
}

if (!function_exists('mention_reply_user')) {
    function mention_reply_user(): string
    {
        $request = app('laraquest');
        return "[{$request->message->reply_to_message->from->first_name}](tg://user?id={$request->message->reply_to_message->from->id})";
    }
}

if (!function_exists('mention_sender_user')) {
    function mention_sender_user(): string
    {
        $request = app('laraquest');
        return "[{$request->message->from->first_name}](tg://user?id={$request->message->from->id})";
    }
}

if (!function_exists('selfDelete')) {
    function selfDelete(): void
    {
        request()->mode(LaraGram\Laraquest\Mode::NO_RESPONSE_CURL)->deleteMessage(chat()->id, message()->message_id);
    }
}