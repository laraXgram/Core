<?php

namespace LaraGram\Keyboard;

class Make
{
    /**
     * Define a row.
     *
     * @param ...$col
     * @return array[]
     */
    public static function row(...$col): array
    {
        return [...$col];
    }

    /**
     * HTTP or tg:// URL to be opened when the button is pressed.
     * Links tg://user?id=<user_id> can be used to mention a user by their identifier without using a username, if this is allowed by their privacy settings.
     *
     * @param string $text
     * @param string $url
     * @return array
     */
    public static function url(string $text, string $url): array
    {
        return [
            'text' => $text,
            'url' => $url
        ];
    }

    /**
     * Data to be sent in a callback query to the bot when the button is pressed, 1-64 bytes
     *
     * @param string $text
     * @param string $callback_data
     * @return array
     */
    public static function callbackData(string $text, string $callback_data): array
    {
        return [
            'text' => $text,
            'callback_data' => $callback_data
        ];
    }

    /**
     * An HTTPS URL used to automatically authorize the user. Can be used as a replacement for the Telegram Login Widget.
     *
     * @param string $text
     * @param string $url
     * @param string|null $forward_text
     * @param string|null $bot_username
     * @param bool|null $request_write_access
     * @return array
     */
    public static function loginUrl(string $text, string $url, string $forward_text = null, string $bot_username = null, bool $request_write_access = null): array
    {
        return [
            'text' => $text,
            'login_url' => [
                'url' => $url,
                'forward_text' => $forward_text,
                'bot_username' => $bot_username,
                'request_write_access' => $request_write_access,
            ]
        ];
    }

    /**
     * Pressing the button will prompt the user to select one of their chats, open that chat and insert the bot's username and the specified inline query in the input field. May be empty, in which case just the bot's username will be inserted.
     * Not supported for messages sent on behalf of a Telegram Business account.
     *
     * @param string $text
     * @param string $switch_inline_query
     * @return array
     */
    public static function switchInlineQuery(string $text, string $switch_inline_query): array
    {
        return [
            'text' => $text,
            'switch_inline_query' => $switch_inline_query
        ];
    }

    /**
     * Pressing the button will insert the bot's username and the specified inline query in the current chat's input field.
     * May be empty, in which case only the bot's username will be inserted.
     *
     * @param string $text
     * @param string $switch_inline_query_current_chat
     * @return array
     */
    public static function switchInlineQueryCurrentChat(string $text, string $switch_inline_query_current_chat): array
    {
        return [
            'text' => $text,
            'switch_inline_query_current_chat' => $switch_inline_query_current_chat
        ];
    }

    /**
     * Pressing the button will prompt the user to select one of their chats of the specified type, open that chat and insert the bot's username and the specified inline query in the input field.
     * Not supported for messages sent on behalf of a Telegram Business account.
     *
     * @param string $text
     * @param string $query
     * @param array $options The options can be an array of
     *  `allow_user_chats` | `allow_bot_chats` | `allow_group_chats` | `allow_channel_chats`
     *  according to the <a href="https://core.telegram.org/bots/api#switchinlinequerychosenchat">documentation</a>.
     * @return array
     */
    public static function switchInlineQueryChosenChat(string $text, string $query = '', array $options = []): array
    {
        return [
            'text' => $text,
            'switch_inline_query_chosen_chat' => [
                'query' => $query,
                ...$options
            ]
        ];
    }

    /**
     * Description of the button that copies the specified text to the clipboard.
     *
     * @param string $text
     * @param string $copy
     * @return array
     */
    public static function copyText(string $text, string $copy): array
    {
        return [
            'text' => $text,
            'copy_text' => [
                'text' => $copy
            ]
        ];
    }

    // TODO: add callbackGame button

    /**
     * send a Pay button. Substrings “⭐” and “XTR” in the button's text will be replaced with a Telegram Star icon.
     *
     * @param string $text
     * @return array
     */
    public static function pay(string $text): array
    {
        return [
            'text' => $text,
            'pay' => true
        ];
    }

    /**
     * Pressing the button will open a list of suitable users. Identifiers of selected users will be sent to the bot in a “users_shared” service message.
     * Available in private chats only.
     *
     * @param string $text
     * @param int|null $id The `request_id` must be a 32-bit number, if empty a random number will be generated for each request.
     * @param int $max_quantity The maximum number of users to be selected; 1-10.
     * @param array $options The options can be an array of
     * `user_is_bot` | `user_is_premium` | `request_name` | `request_username` | `request_photo`
     * according to the <a href="https://core.telegram.org/bots/api#keyboardbuttonrequestusers">documentation</a>.
     * @return array
     */
    public static function requestUsers(string $text, int $id = null, int $max_quantity = 1, array $options = []): array
    {
        return [
            'text' => $text,
            'request_users' => [
                'request_id' => is_null($id) ? rand(1_000_000_000, 9_999_999_999) : $id,
                'max_quantity' => $max_quantity,
                ...$options
            ]
        ];
    }

    /**
     * Pressing the button will open a list of suitable chats. Tapping on a chat will send its identifier to the bot in a “chat_shared” service message.
     * Available in private chats only.
     *
     * @param string $text
     * @param int|null $id The `request_id` must be a 32-bit number, if empty a random number will be generated for each request.
     * @param array $options The options can be an array of
     * `chat_is_channel` | `chat_is_forum` | `chat_has_username` |
     * `chat_is_created` | `user_administrator_rights` | `bot_administrator_rights` |
     * `bot_is_member` | `request_title` | `request_username` | `request_photo`
     * according to the <a href="https://core.telegram.org/bots/api#keyboardbuttonrequestchat">documentation</a>.
     * @return array
     */
    public static function requestChat(string $text, int $id = null, array $options = []): array
    {
        return [
            'text' => $text,
            'request_chat' => [
                'request_id' => is_null($id) ? rand(1_000_000_000, 9_999_999_999) : $id,
                ...$options
            ]
        ];
    }

    /**
     * The user's phone number will be sent as a contact when the button is pressed.
     * Available in private chats only.
     *
     * @param string $text
     * @return array
     */
    public static function requestContact(string $text): array
    {
        return [
            'text' => $text,
            'request_contact' => true
        ];
    }

    /**
     * The user's current location will be sent when the button is pressed.
     * Available in private chats only.
     *
     * @param string $text
     * @return array
     */
    public static function requestLocation(string $text): array
    {
        return [
            'text' => $text,
            'request_location' => true
        ];
    }

    /**
     * The user will be asked to create a poll and send it to the bot when the button is pressed.
     * Available in private chats only.
     *
     * @param string $text
     * @param string $type Type can be `regular` or `quiz`, leave blank for both.
     * @return array
     */
    public static function requestPoll(string $text, string $type = ''): array
    {
        return [
            'text' => $text,
            'request_poll' => [
                'type' => $type
            ]
        ];
    }

    /**
     * The described Web App will be launched when the button is pressed. The Web App will be able to send a “web_app_data” service message.
     * Available in private chats only.
     *
     * @param string $text
     * @param string $url
     * @return array
     */
    public static function webApp(string $text, string $url): array
    {
        return [
            'text' => $text,
            'web_app' => [
                'url' => $url
            ]
        ];
    }
}