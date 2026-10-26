<?php

use LaraGram\Laraquest\Updates\Chat;
use LaraGram\Laraquest\Updates\Message;
use LaraGram\Laraquest\Updates\User;
use LaraGram\Request\Request;

if (!function_exists('bot_connection')) {
    /**
     * Get the name of the bot connection handling the current update.
     *
     * @return string|null
     */
    function bot_connection(): string|null
    {
        $request = app('request');

        return $request instanceof Request ? $request->botConnection() : null;
    }
}

if (!function_exists('chat')) {
    /**
     * Get the chat the current update happened in.
     *
     * @return object|null
     */
    function chat(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('request');
        return match (true) {
            isset($request->message) => $request->message->chat,
            isset($request->edited_message) => $request->edited_message->chat,
            isset($request->channel_post) => $request->channel_post->chat,
            isset($request->edited_channel_post) => $request->edited_channel_post->chat,
            isset($request->business_message) => $request->business_message->chat,
            isset($request->edited_business_message) => $request->edited_business_message->chat,
            isset($request->guest_message) => $request->guest_message->chat,
            isset($request->deleted_business_messages) => $request->deleted_business_messages->chat,
            isset($request->callback_query) => $request->callback_query->message->chat,
            isset($request->message_reaction) => $request->message_reaction->chat,
            isset($request->message_reaction_count) => $request->message_reaction_count->chat,
            isset($request->my_chat_member) => $request->my_chat_member->chat,
            isset($request->chat_member) => $request->chat_member->chat,
            isset($request->chat_join_request) => $request->chat_join_request->chat,
            isset($request->chat_boost) => $request->chat_boost->chat,
            isset($request->removed_chat_boost) => $request->removed_chat_boost->chat,
            isset($request->poll_answer) => $request->poll_answer->voter_chat,
            isset($request->stopped_message_generation) => $request->stopped_message_generation->chat,
            default => Chat::missing()
        };
    }
}

if (!function_exists('user')) {
    /**
     * Get the user who caused the current update.
     *
     * @return object|null
     */
    function user(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('request');
        return match (true) {
            isset($request->message) => $request->message->from,
            isset($request->edited_message) => $request->edited_message->from,
            isset($request->channel_post) => $request->channel_post->from,
            isset($request->edited_channel_post) => $request->edited_channel_post->from,
            isset($request->business_connection) => $request->business_connection->user,
            isset($request->business_message) => $request->business_message->from,
            isset($request->edited_business_message) => $request->edited_business_message->from,
            isset($request->guest_message) => $request->guest_message->from,
            isset($request->message_reaction) => $request->message_reaction->user,
            isset($request->inline_query) => $request->inline_query->from,
            isset($request->chosen_inline_result) => $request->chosen_inline_result->from,
            isset($request->callback_query) => $request->callback_query->from,
            isset($request->shipping_query) => $request->shipping_query->from,
            isset($request->pre_checkout_query) => $request->pre_checkout_query->from,
            isset($request->purchased_paid_media) => $request->purchased_paid_media->from,
            isset($request->poll_answer) => $request->poll_answer->user,
            isset($request->my_chat_member) => $request->my_chat_member->from,
            isset($request->chat_member) => $request->chat_member->from,
            isset($request->chat_join_request) => $request->chat_join_request->from,
            isset($request->chat_boost) => $request->chat_boost->boost->source->user,
            isset($request->removed_chat_boost) => $request->removed_chat_boost->source->user,
            isset($request->subscription) => $request->subscription->user,
            isset($request->managed_bot) => $request->managed_bot->user,
            default => User::missing()
        };
    }
}

if (!function_exists('sender')) {
    /**
     * @return object|null
     */
    function sender(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('request');
        $message = match (true) {
            isset($request->message) => $request->message,
            isset($request->edited_message) => $request->edited_message,
            isset($request->channel_post) => $request->channel_post,
            isset($request->edited_channel_post) => $request->edited_channel_post,
            isset($request->business_message) => $request->business_message,
            isset($request->edited_business_message) => $request->edited_business_message,
            isset($request->guest_message) => $request->guest_message,
            default => null
        };

        return match (true) {
            $message !== null => isset($message->sender_chat) ? $message->sender_chat : $message->from,
            isset($request->message_reaction) => isset($request->message_reaction->user) ? $request->message_reaction->user : $request->message_reaction->actor_chat,
            isset($request->poll_answer) => isset($request->poll_answer->user) ? $request->poll_answer->user : $request->poll_answer->voter_chat,
            default => user()
        };
    }
}

if (!function_exists('text')) {
    /**
     * Get the text of the current message, or its caption for media messages.
     *
     * @return string|null
     */
    function text(): string|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('request');
        $message = match (true) {
            isset($request->message) => $request->message,
            isset($request->edited_message) => $request->edited_message,
            isset($request->channel_post) => $request->channel_post,
            isset($request->edited_channel_post) => $request->edited_channel_post,
            isset($request->business_message) => $request->business_message,
            isset($request->edited_business_message) => $request->edited_business_message,
            isset($request->guest_message) => $request->guest_message,
            default => null
        };

        return $message->text ?? $message->caption ?? null;
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
    /**
     * Get the message of the current update.
     *
     * For a callback query this is the message its button is attached to, which
     * is absent for inline-mode messages.
     *
     * @return object|null
     */
    function message(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('request');
        return match (true) {
            isset($request->message) => $request->message,
            isset($request->edited_message) => $request->edited_message,
            isset($request->channel_post) => $request->channel_post,
            isset($request->edited_channel_post) => $request->edited_channel_post,
            isset($request->business_message) => $request->business_message,
            isset($request->edited_business_message) => $request->edited_business_message,
            isset($request->guest_message) => $request->guest_message,
            isset($request->callback_query) => $request->callback_query->message,
            default => Message::missing()
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
            isset($request->edited_message) => $request->edited_message,
            isset($request->edited_channel_post) => $request->edited_channel_post,
            isset($request->edited_business_message) => $request->edited_business_message,
            isset($request->callback_query) => $request->callback_query->message,
            default => Message::missing()
        };
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

if (!function_exists('stopped_message_generation')) {
    function stopped_message_generation(): object|null
    {
        /**
         * @var Request $request ;
         */
        $request = app('request');
        return $request->stopped_message_generation;
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
        $reply = message()?->reply_to_message ?? null;

        if ($reply === null || isset($reply->sender_chat) || ! isset($reply->from)) {
            return false;
        }

        return mention_user_by_id($reply->from->id, $reply->from->first_name, $parse_mode);
    }
}

if (!function_exists('mention_sender_user')) {
    function mention_sender_user($parse_mode = 'markdownv2'): false|string
    {
        $message = message();

        if (blank($message) || isset($message->sender_chat) || ! isset($message->from)) {
            return false;
        }

        return mention_user_by_id($message->from->id, $message->from->first_name, $parse_mode);
    }
}

if (!function_exists('self_delete')) {
    function self_delete($methods = ['*']): void
    {
        $request = app('request');

        if ($methods !== ['*'] && ! in_array($request->method(), \LaraGram\Support\Arr::wrap($methods))) {
            return;
        }

        $chatId = chat()->id ?? null;
        $messageId = message()->message_id ?? null;

        if ($chatId !== null && $messageId !== null) {
            $request->mode(LaraGram\Laraquest\Mode::NO_RESPONSE_CURL)->deleteMessage($chatId, $messageId);
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
