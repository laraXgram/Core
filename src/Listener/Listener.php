<?php

namespace LaraGram\Listener;

use Closure;
use LaraGram\Support\Traits\Macroable;

final class Listener extends Matcher
{
    use Macroable;

    public function __construct(private readonly Group $group) { }

    public function on(string|array $pattern, Closure|array|string $action)
    {
        return $this->match('text', $action, $pattern);
    }

    public function onText(string|array $pattern, Closure|array|string $action)
    {
        return $this->match('text', $action, $pattern);
    }

    public function onCommand(string|array $pattern, Closure|array|string $action)
    {
        return $this->match('command', $action, $pattern);
    }

    public function onAnimation(Closure|array|string $action, array|string|null $file_id = null)
    {
        return $this->match('animation', $action, $file_id);
    }

    public function onAudio(Closure|array|string $action, array|string|null $file_id = null)
    {
        return $this->match('audio', $action, $file_id);
    }

    public function onDocument(Closure|array|string $action, array|string|null $file_id = null)
    {
        return $this->match('document', $action, $file_id);
    }

    public function onPhoto(Closure|array|string $action, array|string|null $file_id = null)
    {
        return $this->match('photo', $action, $file_id);
    }

    public function onSticker(Closure|array|string $action, array|string|null $file_id = null)
    {
        return $this->match('sticker', $action, $file_id);
    }

    public function onVideo(Closure|array|string $action, array|string|null $file_id = null)
    {
        return $this->match('video', $action, $file_id);
    }

    public function onVideoNote(Closure|array|string $action, array|string|null $file_id = null)
    {
        return $this->match('video_note', $action, $file_id);
    }

    public function onVoice(Closure|array|string $action, array|string|null $file_id = null)
    {
        return $this->match('voice', $action, $file_id);
    }

    public function onDice(Closure|array|string $action, string|null $emoji = null, string|int|null $value = null)
    {
        return $this->match('dice', $action, [$emoji, $value]);
    }

    public function onGame(Closure|array|string $action)
    {
        return $this->match('game', $action, null);
    }

    public function onPoll(Closure|array|string $action)
    {
        return $this->match('poll', $action, null);
    }

    public function onVenue(Closure|array|string $action)
    {
        return $this->match('venue', $action, null);
    }

    public function onLocation(Closure|array|string $action)
    {
        return $this->match('location', $action, null);
    }

    public function onNewChatMembers(Closure|array|string $action)
    {
        return $this->match('new_chat_members', $action, null);
    }

    public function onLeftChatMember(Closure|array|string $action)
    {
        return $this->match('left_chat_member', $action, null);
    }

    public function onNewChatTitle(Closure|array|string $action)
    {
        return $this->match('new_chat_title', $action, null);
    }

    public function onNewChatPhoto(Closure|array|string $action)
    {
        return $this->match('new_chat_photo', $action, null);
    }

    public function onDeleteChatPhoto(Closure|array|string $action)
    {
        return $this->match('delete_chat_photo', $action, null);
    }

    public function onGroupChatCreated(Closure|array|string $action)
    {
        return $this->match('group_chat_created', $action, null);
    }

    public function onSuperGroupChatCreated(Closure|array|string $action)
    {
        return $this->match('supergroup_chat_created', $action, null);
    }

    public function onMessageAutoDeleteTimerChanged(Closure|array|string $action)
    {
        return $this->match('message_auto_delete_timer_changed', $action, null);
    }

    public function onMigrateToChatId(Closure|array|string $action)
    {
        return $this->match('migrate_to_chat_id', $action, null);
    }

    public function onMigrateFromChatId(Closure|array|string $action)
    {
        return $this->match('migrate_from_chat_id', $action, null);
    }

    public function onPinnedMessage(Closure|array|string $action)
    {
        return $this->match('pinned_message', $action, null);
    }

    public function onInvoice(Closure|array|string $action)
    {
        return $this->match('invoice', $action, null);
    }

    public function onSuccessfulPayment(Closure|array|string $action)
    {
        return $this->match('successful_payment', $action, null);
    }

    public function onConnectedWebsite(Closure|array|string $action)
    {
        return $this->match('connected_website', $action, null);
    }

    public function onPassportData(Closure|array|string $action)
    {
        return $this->match('passport_data', $action, null);
    }

    public function onProximityAlertTriggered(Closure|array|string $action)
    {
        return $this->match('proximity_alert_triggered', $action, null);
    }

    public function onForumTopicCreated(Closure|array|string $action)
    {
        return $this->match('forum_topic_created', $action, null);
    }

    public function onForumTopicEdited(Closure|array|string $action)
    {
        return $this->match('forum_topic_edited', $action, null);
    }

    public function onForumTopicClosed(Closure|array|string $action)
    {
        return $this->match('forum_topic_closed', $action, null);
    }

    public function onForumTopicReopened(Closure|array|string $action)
    {
        return $this->match('forum_topic_reopened', $action, null);
    }

    public function onVideoChatScheduled(Closure|array|string $action)
    {
        return $this->match('video_chat_scheduled', $action, null);
    }

    public function onVideoChatStarted(Closure|array|string $action)
    {
        return $this->match('video_chat_started', $action, null);
    }

    public function onVideoChatEnded(Closure|array|string $action)
    {
        return $this->match('video_chat_ended', $action, null);
    }

    public function onVideoChatParticipantsInvited(Closure|array|string $action)
    {
        return $this->match('video_chat_participants_invited', $action, null);
    }

    public function onWebAppData(Closure|array|string $action)
    {
        return $this->match('web_app_data', $action, null);
    }

    public function onMessage(Closure|array|string $action)
    {
        return $this->match('message', $action, null);
    }

    public function onEditedMessage(Closure|array|string $action)
    {
        return $this->match('edited_message', $action, null);
    }

    public function onChannelPost(Closure|array|string $action)
    {
        return $this->match('channel_post', $action, null);
    }

    public function onEditedChannelPost(Closure|array|string $action)
    {
        return $this->match('edited_channel_post', $action, null);
    }

    public function onInlineQuery(Closure|array|string $action)
    {
        return $this->match('inline_query', $action, null);
    }

    public function onChosenInlineResult(Closure|array|string $action)
    {
        return $this->match('chosen_inline_result', $action, null);
    }

    public function onCallbackQuery(Closure|array|string $action)
    {
        return $this->match('callback_query', $action, null);
    }

    public function onShippingQuery(Closure|array|string $action)
    {
        return $this->match('shipping_query', $action, null);
    }

    public function onPreCheckoutQuery(Closure|array|string $action)
    {
        return $this->match('pre_checkout_query', $action, null);
    }

    public function onPollAnswer(Closure|array|string $action)
    {
        return $this->match('poll_answer', $action, null);
    }

    public function onMyChatMember(Closure|array|string $action)
    {
        return $this->match('my_chat_member', $action, null);
    }

    public function onChatMember(Closure|array|string $action)
    {
        return $this->match('chat_member', $action, null);
    }

    public function onChatJoinRequest(Closure|array|string $action)
    {
        return $this->match('chat_join_request', $action, null);
    }

    public function onCallbackQueryData(array|string $pattern, Closure|array|string $action)
    {
        return $this->match('callback_query_data', $action, $pattern);
    }

    public function onMessageType(array|string $type, Closure|array|string $action)
    {
        return $this->match('message_type', $action, $type);
    }

    public function onAny(Closure|array|string $action)
    {
        return $this->match('any', $action, null);
    }

    public function onReferral(Closure|array|string $action)
    {
        return $this->match('referral', $action, null);
    }

    public function onHashtag(Closure|array|string $action)
    {
        return $this->match('hashtag', $action, null);
    }

    public function onCashtag(Closure|array|string $action)
    {
        return $this->match('cashtag', $action, null);
    }

    public function onMention(Closure|array|string $action)
    {
        return $this->match('mention', $action, null);
    }

    public function onAddMember(Closure|array|string $action)
    {
        return $this->match('add_member', $action, null);
    }

    public function onJoinMember(Closure|array|string $action)
    {
        return $this->match('join_member', $action, null);
    }

    public function group(array $attributes, callable $callback)
    {
        foreach ($attributes as $key => $value) {
            match ($key) {
                'scope' => $this->group->scope($value),
                'outOfScope' => $this->group->outOfScope($value),
                'can' => $this->group->can($value),
                'canNot' => $this->group->canNot($value),
                'hasReply' => $this->group->hasReply(),
                'hasNotReply' => $this->group->hasNotReply()
            };
        }

        $this->group->group($callback);
    }

    public function scope(array|string $scopes): Group
    {
        $this->group->scope($scopes);
        return $this->group;
    }

    public function outOfScope(array|string $scopes): Group
    {
        $this->group->outOfScope($scopes);
        return $this->group;
    }

    public function can(array|string $roles): Group
    {
        $this->group->can($roles);
        return $this->group;
    }

    public function canNot(array|string $roles): Group
    {
        $this->group->canNot($roles);
        return $this->group;
    }

    public function hasReply(): Group
    {
        $this->group->hasReply();
        return $this->group;
    }

    public function hasNotReply(): Group
    {
        $this->group->hasNotReply();
        return $this->group;
    }

    public function controller(string $controller): Group
    {
        $this->controller = $controller;
        return $this->group;
    }
}