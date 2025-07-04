<?php

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