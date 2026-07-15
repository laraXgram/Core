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
            $request->callback_query != null => $request->callback_query->message->chat,
            $request->edited_message != null => $request->edited_message->chat,
            $request->channel_post != null => $request->channel_post->chat,
            $request->edited_channel_post != null => $request->edited_channel_post->chat,
            $request->business_message != null => $request->business_message->chat,
            $request->edited_business_message != null => $request->edited_business_message->chat,
            $request->guest_message != null => $request->guest_message->chat,
            $request->deleted_business_messages != null => $request->deleted_business_messages->chat,
            $request->message_reaction != null => $request->message_reaction->chat,
            $request->message_reaction_count != null => $request->message_reaction_count->chat,
            $request->my_chat_member != null => $request->my_chat_member->chat,
            $request->chat_member != null => $request->chat_member->chat,
            $request->chat_join_request != null => $request->chat_join_request->chat,
            $request->chat_boost != null => $request->chat_boost->chat,
            $request->removed_chat_boost != null => $request->removed_chat_boost->chat,
            $request->poll_answer != null => $request->poll_answer->voter_chat,
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
            $request->guest_message != null => $request->guest_message->from,
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
            $request->purchased_paid_media != null => $request->purchased_paid_media->from,
            $request->chat_boost != null => $request->chat_boost->boost->source->user,
            $request->removed_chat_boost != null => $request->removed_chat_boost->source->user,
            $request->subscription != null => $request->subscription->user,
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
        $request = app('request');
        return match (true) {
            $request->message != null && isset($request->message->text) => $request->message->text,
            $request->edited_message != null => $request->edited_message->text,
            $request->channel_post != null => $request->channel_post->caption,
            $request->edited_channel_post != null => $request->edited_channel_post->caption,
            $request->business_message != null => $request->business_message->text,
            $request->edited_business_message != null => $request->edited_business_message->text,
            $request->guest_message != null => $request->guest_message->text,
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
        return match (true) {
            $request->message != null => $request->message,
            $request->edited_message != null => $request->edited_message,
            $request->channel_post != null => $request->channel_post,
            $request->edited_channel_post != null => $request->edited_channel_post,
            $request->business_message != null => $request->business_message,
            $request->edited_business_message != null => $request->edited_business_message,
            $request->guest_message != null => $request->guest_message,
            $request->callback_query != null => $request->callback_query->message,
            default => null
        };
    }
}

if (!function_exists('edited_message')) {
    function edited_message(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('request');
        return match (true) {
            $request->edited_message != null => $request->edited_message,
            $request->edited_channel_post != null => $request->edited_channel_post,
            $request->edited_business_message != null => $request->edited_business_message,
            $request->callback_query != null => $request->callback_query->message,
            default => null
        };    }
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

if (!function_exists('guest_message')) {
    function guest_message(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('request');
        return $request->guest_message;
    }
}

if (!function_exists('purchased_paid_media')) {
    function purchased_paid_media(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('request');
        return $request->purchased_paid_media;
    }
}

if (!function_exists('managed_bot')) {
    function managed_bot(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('request');
        return $request->managed_bot;
    }
}

if (!function_exists('subscription')) {
    function subscription(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('request');
        return $request->subscription;
    }
}

if (!function_exists('reaction')) {
    /**
     * Get the new reactions of a message_reaction update.
     *
     * @return array
     */
    function reaction(): array
    {
        /**
         * @var Request $request ;
         */
        $request = app('request');
        return $request->message_reaction->new_reaction ?? [];
    }
}

if (!function_exists('inline_url')) {
    function inline_url($text, $url, $parse_mode = 'markdownv2'): false|string
    {
        return match (strtolower($parse_mode)){
            'markdown', 'markdownv2' => "[{$text}]({$url})",
            'html' => "<a href='{$url}'>{$text}</a>",
            default => false
        };
    }
}

if (!function_exists('code')) {
    function code($text, $parse_mode = 'markdownv2'): false|string
    {
        return match (strtolower($parse_mode)){
            'markdown', 'markdownv2' => "`{$text}`",
            'html' => "<code>{$text}</code>",
            default => false
        };
    }
}

if (!function_exists('pre')) {
    function pre($text, $parse_mode = 'markdownv2'): false|string
    {
        return match (strtolower($parse_mode)){
            'markdown', 'markdownv2' => "```
{$text}
```",
            'html' => "<pre>{$text}</pre>",
            default => false
        };
    }
}

if (!function_exists('inline_code')) {
    function inline_code($code, $lang = '', $parse_mode = 'markdownv2'): false|string
    {
        return match (strtolower($parse_mode)){
            'markdown', 'markdownv2' => "```{$lang}
{$code}```",
            'html' => "<pre><code class=\"lanquage-{$lang}\">{$code}</code></pre>",
            default => false
        };
    }
}

if (!function_exists('spoiler')) {
    function spoiler($text, $parse_mode = 'markdownv2'): false|string
    {
        return match (strtolower($parse_mode)){
            'markdown', 'markdownv2' => "||{$text}||",
            'html' => "<tg-spoiler>{$text}</tg-spoiler>",
            default => false
        };
    }
}

if (!function_exists('underline')) {
    function underline($text, $parse_mode = 'markdownv2'): string
    {
        return match (strtolower($parse_mode)){
            'markdown', 'markdownv2' => "__{$text}_",
            'html' => "<u>{$text}</u>",
            default => false
        };
    }
}

if (!function_exists('italic')) {
    function italic($text, $parse_mode = 'markdownv2'): false|string
    {
        return match (strtolower($parse_mode)){
            'markdown', 'markdownv2' => "_{$text}_",
            'html' => "<i>{$text}</i>",
            default => false
        };
    }
}

if (!function_exists('bold')) {
    function bold($text, $parse_mode = 'markdownv2'): false|string
    {
        return match (strtolower($parse_mode)){
            'markdown', 'markdownv2' => "*{$text}*",
            'html' => "<b>{$text}</b>",
            default => false
        };
    }
}

if (!function_exists('strikethrough')) {
    function strikethrough($text, $parse_mode = 'markdownv2'): false|string
    {
        return match (strtolower($parse_mode)){
            'markdown', 'markdownv2' => "~{$text}~",
            'html' => "<s>{$text}</s>",
            default => false
        };
    }
}

if (!function_exists('custom_emoji')) {
    function custom_emoji($id, $fallback = '👍', $parse_mode = 'markdownv2'): false|string
    {
        return match (strtolower($parse_mode)){
            'markdown', 'markdownv2' => "![{$fallback}](tg://emoji?id={$id})",
            'html' => "<tg-emoji emoji-id=\"{$id}\">{$fallback}</tg-emoji>",
            default => false
        };
    }
}

if (!function_exists('tg_time')) {
    function tg_time($text, $time, $format = '', $parse_mode = 'markdownv2'): false|string
    {
        return match (strtolower($parse_mode)){
            'markdown', 'markdownv2' => "![{$text}](tg://time?unix={$time}&format={$format})",
            'html' => "<tg-time unix=\"{$time}\" format=\"{$format}\">{$text}</tg-time>",
            default => false
        };
    }
}

if (!function_exists('blockquote')) {
    function blockquote($text, $expandable = false, $parse_mode = 'markdownv2'): false|string
    {
        return match (strtolower($parse_mode)){
            'markdown', 'markdownv2' => ($expandable ? '**>' : '>') . implode("\n>", explode(PHP_EOL, trim($text, PHP_EOL))) . "||",
            'html' => ($expandable ? '<blockquote expandable>' : '<blockquote>') . "{$text}</blockquote>",
            default => false
        };
    }
}

if (!function_exists('mention_user_by_id')) {
    function mention_user_by_id($user_id, $text, $parse_mode = 'markdownv2'): false|string
    {
        return match (strtolower($parse_mode)){
            'markdown', 'markdownv2' => "[{$text}](tg://user?id={$user_id})",
            'html' => "<a href='tg://user?id={$user_id}'>{$text}</a>",
            default => false
        };
    }
}

if (!function_exists('mention_reply_user')) {
    function mention_reply_user($parse_mode = 'markdownv2'): false|string
    {
        $message = message();
        return match(strtolower($parse_mode)) {
            'markdown', 'markdownv2' => "[{$message->reply_to_message->from->first_name}](tg://user?id={$message->reply_to_message->from->id})",
            'html' => "<a href=\"tg://user?id={$message->reply_to_message->from->id}\">{$message->reply_to_message->from->first_name}</a>",
            default =>  false
        };
    }
}

if (!function_exists('mention_sender_user')) {
    function mention_sender_user($parse_mode = 'markdownv2'): false|string
    {
        $message = message();
        return match(strtolower($parse_mode)) {
            'markdown', 'markdownv2' => "[{$message->from->first_name}](tg://user?id={$message->from->id})",
            'html' => "<a href=\"tg://user?id={$message->from->id}\">{$message->from->first_name}</a>",
            default =>  false
        };
    }
}

if (!function_exists('self_delete')) {
    function self_delete($methods = ['*']): void
    {
        $request = app('request');
        if ($methods === ['*'] || in_array($request->method(), \LaraGram\Support\Arr::wrap($methods))) {
            $request->mode(LaraGram\Laraquest\Mode::NO_RESPONSE_CURL)->deleteMessage(chat()->id, message()->message_id);
        }
    }
}

if (!function_exists('selfDelete')) {
    /**
     * @deprecated use `self_delete`
     */
    function selfDelete($methods = ['*']): void
    {
        self_delete($methods);
    }
}

if (!function_exists('mentionSenderUser')) {
    /**
     * @deprecated use `mention_sender_user`
     */
    function mentionSenderUser($parse_mode = 'markdownv2'): string|false
    {
        return mention_sender_user($parse_mode);
    }
}

if (!function_exists('mentionReplyUser')) {
    /**
     * @deprecated use `mention_reply_user`
     */
    function mentionReplyUser($parse_mode = 'markdownv2'): string|false
    {
        return mention_reply_user($parse_mode);
    }
}

if (!function_exists('mentionUserById')) {
    /**
     * @deprecated use `mention_user_by_id`
     */
    function mentionUserById($user_id, $text, $parse_mode = 'markdownv2'): string|false
    {
        return mention_user_by_id($user_id, $text, $parse_mode);
    }
}
