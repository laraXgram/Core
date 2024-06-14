<?php

namespace LaraGram\Listener;

use Closure;

final class Listener extends Matcher
{
    public function on(string|array $pattern, Closure $action)
    {
        return $this->match('text', $action, $pattern);
    }

    public function onText(string|array $pattern, Closure $action)
    {
        return $this->match('text', $action, $pattern);
    }

    public function onCommand(string|array $pattern, Closure $action)
    {
        return $this->match('command', $action, $pattern);
    }

    public function onAnimation(callable $action, array|string|null $file_id = null)
    {
        return $this->match('animation', $action, $file_id);
    }

    public function onAudio(callable $action, array|string|null $file_id = null)
    {
        return $this->match('audio', $action, $file_id);
    }

    public function onDocument(callable $action, array|string|null $file_id = null)
    {
        return $this->match('document', $action, $file_id);
    }

    public function onPhoto(callable $action, array|string|null $file_id = null)
    {
        return $this->match('photo', $action, $file_id);
    }

    public function onSticker(callable $action, array|string|null $file_id = null)
    {
        return $this->match('sticker', $action, $file_id);
    }

    public function onVideo(callable $action, array|string|null $file_id = null)
    {
        return $this->match('video', $action, $file_id);
    }

    public function onVideoNote(callable $action, array|string|null $file_id = null)
    {
        return $this->match('video_note', $action, $file_id);
    }

    public function onVoice(callable $action, array|string|null $file_id = null)
    {
        return $this->match('voice', $action, $file_id);
    }

    public function onDice(callable $action, string|null $emoji = null, string|int|null $value = null)
    {
        return $this->match('dice', $action, [$emoji, $value]);
    }

    public function onGame(callable $action)
    {
        return $this->match('game', $action, null);
    }

    public function onPoll(callable $action)
    {
        return $this->match('poll', $action, null);
    }

    public function onVenue(callable $action)
    {
        return $this->match('venue', $action, null);
    }

    public function onLocation(callable $action)
    {
        return $this->match('location', $action, null);
    }

    public function onNewChatMembers(callable $action)
    {
        return $this->match('new_chat_members', $action, null);
    }

    public function onLeftChatMember(callable $action)
    {
        return $this->match('left_chat_member', $action, null);
    }

    public function onNewChatTitle(callable $action)
    {
        return $this->match('new_chat_title', $action, null);
    }

    public function onNewChatPhoto(callable $action)
    {
        return $this->match('new_chat_photo', $action, null);
    }

    public function onDeleteChatPhoto(callable $action)
    {
        return $this->match('delete_chat_photo', $action, null);
    }

    public function onGroupChatCreated(callable $action)
    {
        return $this->match('group_chat_created', $action, null);
    }

    public function onSuperGroupChatCreated(callable $action)
    {
        return $this->match('supergroup_chat_created', $action, null);
    }

    public function onMessageAutoDeleteTimerChanged(callable $action)
    {
        return $this->match('message_auto_delete_timer_changed', $action, null);
    }

    public function onMigrateToChatId(callable $action)
    {
        return $this->match('migrate_to_chat_id', $action, null);
    }

    public function onMigrateFromChatId(callable $action)
    {
        return $this->match('migrate_from_chat_id', $action, null);
    }

    public function onPinnedMessage(callable $action)
    {
        return $this->match('pinned_message', $action, null);
    }

    public function onInvoice(callable $action)
    {
        return $this->match('invoice', $action, null);
    }

    public function onSuccessfulPayment(callable $action)
    {
        return $this->match('successful_payment', $action, null);
    }

    public function onConnectedWebsite(callable $action)
    {
        return $this->match('connected_website', $action, null);
    }

    public function onPassportData(callable $action)
    {
        return $this->match('passport_data', $action, null);
    }

    public function onProximityAlertTriggered(callable $action)
    {
        return $this->match('proximity_alert_triggered', $action, null);
    }

    public function onForumTopicCreated(callable $action)
    {
        return $this->match('forum_topic_created', $action, null);
    }

    public function onForumTopicEdited(callable $action)
    {
        return $this->match('forum_topic_edited', $action, null);
    }

    public function onForumTopicClosed(callable $action)
    {
        return $this->match('forum_topic_closed', $action, null);
    }

    public function onForumTopicReopened(callable $action)
    {
        return $this->match('forum_topic_reopened', $action, null);
    }

    public function onVideoChatScheduled(callable $action)
    {
        return $this->match('video_chat_scheduled', $action, null);
    }

    public function onVideoChatStarted(callable $action)
    {
        return $this->match('video_chat_started', $action, null);
    }

    public function onVideoChatEnded(callable $action)
    {
        return $this->match('video_chat_ended', $action, null);
    }

    public function onVideoChatParticipantsInvited(callable $action)
    {
        return $this->match('video_chat_participants_invited', $action, null);
    }

    public function onWebAppData(callable $action)
    {
        return $this->match('web_app_data', $action, null);
    }

    public function onMessage(callable $action)
    {
        return $this->match('message', $action, null);
    }

    public function onEditedMessage(callable $action)
    {
        return $this->match('edited_message', $action, null);
    }

    public function onChannelPost(callable $action)
    {
        return $this->match('channel_post', $action, null);
    }

    public function onEditedChannelPost(callable $action)
    {
        return $this->match('edited_channel_post', $action, null);
    }

    public function onInlineQuery(callable $action)
    {
        return $this->match('inline_query', $action, null);
    }

    public function onChosenInlineResult(callable $action)
    {
        return $this->match('chosen_inline_result', $action, null);
    }

    public function onCallbackQuery(callable $action)
    {
        return $this->match('callback_query', $action, null);
    }

    public function onShippingQuery(callable $action)
    {
        return $this->match('shipping_query', $action, null);
    }

    public function onPreCheckoutQuery(callable $action)
    {
        return $this->match('pre_checkout_query', $action, null);
    }

    public function onPollAnswer(callable $action)
    {
        return $this->match('poll_answer', $action, null);
    }

    public function onMyChatMember(callable $action)
    {
        return $this->match('my_chat_member', $action, null);
    }

    public function onChatMember(callable $action)
    {
        return $this->match('chat_member', $action, null);
    }

    public function onChatJoinRequest(callable $action)
    {
        return $this->match('chat_join_request', $action, null);
    }

    public function onCallbackQueryData(array|string $pattern, callable $action)
    {
        return $this->match('callback_query_data', $action, $pattern);
    }

    public function onMessageType(array|string $type, callable $action)
    {
        return $this->match('message_type', $action, $type);
    }

    public function onAny(callable $action)
    {
        return $this->match('any', $action, null);
    }

    public function onReferral(callable $action)
    {
        return $this->match('referral', $action, null);

    }
}