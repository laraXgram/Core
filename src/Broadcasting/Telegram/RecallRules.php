<?php

namespace LaraGram\Broadcasting\Telegram;

class RecallRules
{
    /**
     * The chat permissions granted when a restriction is lifted.
     *
     * @var array<int, string>
     */
    public const PERMISSIONS = [
        'can_send_messages', 'can_send_audios', 'can_send_documents', 'can_send_photos',
        'can_send_videos', 'can_send_video_notes', 'can_send_voice_notes', 'can_send_polls',
        'can_send_other_messages', 'can_add_web_page_previews', 'can_change_info',
        'can_invite_users', 'can_pin_messages', 'can_manage_topics',
    ];

    /**
     * The administrator rights removed when a promotion is undone.
     *
     * @var array<int, string>
     */
    public const RIGHTS = [
        'is_anonymous', 'can_manage_chat', 'can_delete_messages', 'can_manage_video_chats',
        'can_restrict_members', 'can_promote_members', 'can_change_info', 'can_invite_users',
        'can_post_stories', 'can_edit_stories', 'can_delete_stories', 'can_post_messages',
        'can_edit_messages', 'can_pin_messages', 'can_manage_topics', 'can_manage_direct_messages',
    ];

    /**
     * The methods that swap with each other.
     *
     * @var array<string, string>
     */
    protected const OPPOSITES = [
        'pinChatMessage' => 'unpinChatMessage',
        'unpinChatMessage' => 'pinChatMessage',
        'banChatMember' => 'unbanChatMember',
        'unbanChatMember' => 'banChatMember',
        'banChatSenderChat' => 'unbanChatSenderChat',
        'unbanChatSenderChat' => 'banChatSenderChat',
        'closeForumTopic' => 'reopenForumTopic',
        'reopenForumTopic' => 'closeForumTopic',
        'closeGeneralForumTopic' => 'reopenGeneralForumTopic',
        'reopenGeneralForumTopic' => 'closeGeneralForumTopic',
        'hideGeneralForumTopic' => 'unhideGeneralForumTopic',
        'unhideGeneralForumTopic' => 'hideGeneralForumTopic',
    ];

    /**
     * The parameters kept when a method is swapped with its opposite.
     *
     * @var array<int, string>
     */
    protected const KEPT = ['user_id', 'sender_chat_id', 'message_thread_id', 'business_connection_id'];

    /**
     * The methods that leave nothing behind to undo.
     *
     * @var array<int, string>
     */
    protected const HARMLESS = ['sendChatAction', 'getChat', 'getChatMember', 'getChatMemberCount', 'getChatAdministrators'];

    /**
     * The inverses registered by the application.
     *
     * @var array<string, callable>
     */
    protected static array $custom = [];

    /**
     * Register the inverse of a Bot API method.
     *
     * The callback receives the step that was broadcast and returns the action
     * that undoes it, or null when there is nothing to undo.
     *
     * @param  string  $method
     * @param  callable(\LaraGram\Broadcasting\Telegram\Action): (\LaraGram\Broadcasting\Telegram\Action|null)  $callback
     * @return void
     */
    public static function using(string $method, callable $callback): void
    {
        static::$custom[$method] = $callback;
    }

    /**
     * Forget the registered inverses.
     *
     * @return void
     */
    public static function flush(): void
    {
        static::$custom = [];
    }

    /**
     * Determine if a step can be recalled.
     *
     * @param  \LaraGram\Broadcasting\Telegram\Action  $step
     * @return bool
     */
    public static function supports(Action $step): bool
    {
        return isset(static::$custom[$step->method])
            || in_array($step->method, static::HARMLESS, true)
            || static::sends($step)
            || array_key_exists($step->method, array_filter(static::OPPOSITES))
            || in_array($step->method, ['restrictChatMember', 'promoteChatMember', 'setMessageReaction'], true);
    }

    /**
     * Get the action that undoes a step, or null when there is nothing to undo.
     *
     * @param  \LaraGram\Broadcasting\Telegram\Action  $step
     * @return \LaraGram\Broadcasting\Telegram\Action|null
     */
    public static function inverse(Action $step): ?Action
    {
        if (isset(static::$custom[$step->method])) {
            return (static::$custom[$step->method])($step);
        }

        if (in_array($step->method, static::HARMLESS, true)) {
            return null;
        }

        if (static::sends($step)) {
            return Action::make('deleteMessages');
        }

        $kept = array_intersect_key($step->parameters, array_flip(static::KEPT));

        if (! empty(static::OPPOSITES[$step->method])) {
            $inverse = Action::make(static::OPPOSITES[$step->method], $kept);

            return $inverse->method === 'unbanChatMember'
                ? $inverse->with(['only_if_banned' => true])
                : $inverse;
        }

        return match ($step->method) {
            'restrictChatMember' => Action::make('restrictChatMember', $kept + [
                'permissions' => array_fill_keys(static::PERMISSIONS, true),
            ]),
            'promoteChatMember' => Action::make('promoteChatMember', $kept + array_fill_keys(static::RIGHTS, false)),
            'setMessageReaction' => Action::make('setMessageReaction', ['reaction' => []]),
            default => null,
        };
    }

    /**
     * Determine if a step sent or edited messages, so deleting them undoes it.
     *
     * @param  \LaraGram\Broadcasting\Telegram\Action  $step
     * @return bool
     */
    protected static function sends(Action $step): bool
    {
        return $step->isTemplate()
            || str_starts_with($step->method, 'send')
            || str_starts_with($step->method, 'edit')
            || str_starts_with($step->method, 'copy')
            || str_starts_with($step->method, 'forward');
    }
}
