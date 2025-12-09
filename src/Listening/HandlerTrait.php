<?php

namespace LaraGram\Listening;

use Closure;
use LaraGram\Support\Str;

trait HandlerTrait
{
    public function on(string $pattern, Closure|array|string $action)
    {
        return $this->addListen('TEXT', $pattern, $action);
    }

    public function onText(string $pattern, Closure|array|string $action)
    {
        return $this->addListen('TEXT', $pattern, $action);
    }

    public function onCommand(string $pattern, Closure|array|string $action)
    {
        return $this->addListen('COMMAND', Str::replaceFirst('/', '', $pattern), $action);
    }

    public function onAnimation(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'animation', $action);
    }

    public function onAudio(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'audio', $action);
    }

    public function onDocument(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'document', $action);
    }

    public function onPhoto(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'photo', $action);
    }

    public function onSticker(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'sticker', $action);
    }

    public function onVideo(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'video', $action);
    }

    public function onVideoNote(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'video_note', $action);
    }

    public function onVoice(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'voice', $action);
    }

    public function onDice(Closure|array|string $action, string|array $emoji = 'any', int|array $value = 0)
    {
        $emoji = is_array($emoji) ? implode('|', $emoji) : $emoji;
        $value = is_array($value) ? implode('|', $value) : (string)$value;
        return $this->addListen('DICE', "{$emoji},{$value}", $action);
    }

    public function onGame(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'game', $action);
    }

    public function onPoll(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'poll', $action);
    }

    public function onVenue(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'venue', $action);
    }

    public function onLocation(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'location', $action);
    }

    public function onNewChatMembers(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'new_chat_members', $action);
    }

    public function onLeftChatMember(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'left_chat_member', $action);
    }

    public function onNewChatTitle(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'new_chat_title', $action);
    }

    public function onNewChatPhoto(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'new_chat_photo', $action);
    }

    public function onDeleteChatPhoto(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'delete_chat_photo', $action);
    }

    public function onGroupChatCreated(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'group_chat_created', $action);
    }

    public function onSuperGroupChatCreated(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'supergroup_chat_created', $action);
    }

    public function onMessageAutoDeleteTimerChanged(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'message_auto_delete_timer_changed', $action);
    }

    public function onMigrateToChatId(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'migrate_to_chat_id', $action);
    }

    public function onMigrateFromChatId(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'migrate_from_chat_id', $action);
    }

    public function onPinnedMessage(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'pinned_message', $action);
    }

    public function onInvoice(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'invoice', $action);
    }

    public function onSuccessfulPayment(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'successful_payment', $action);
    }

    public function onConnectedWebsite(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'connected_website', $action);
    }

    public function onPassportData(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'passport_data', $action);
    }

    public function onProximityAlertTriggered(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'proximity_alert_triggered', $action);
    }

    public function onForumTopicCreated(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'forum_topic_created', $action);
    }

    public function onForumTopicEdited(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'forum_topic_edited', $action);
    }

    public function onForumTopicClosed(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'forum_topic_closed', $action);
    }

    public function onForumTopicReopened(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'forum_topic_reopened', $action);
    }

    public function onVideoChatScheduled(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'video_chat_scheduled', $action);
    }

    public function onVideoChatStarted(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'video_chat_started', $action);
    }

    public function onVideoChatEnded(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'video_chat_ended', $action);
    }

    public function onVideoChatParticipantsInvited(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'video_chat_participants_invited', $action);
    }

    public function onWebAppData(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'web_app_data', $action);
    }

    public function onMessage(Closure|array|string $action)
    {
        return $this->addListen('UPDATE', 'message', $action);
    }

    public function onEditedMessage(Closure|array|string $action)
    {
        return $this->addListen('UPDATE', 'edited_message', $action);
    }

    public function onChannelPost(Closure|array|string $action)
    {
        return $this->addListen('UPDATE', 'channel_post', $action);
    }

    public function onEditedChannelPost(Closure|array|string $action)
    {
        return $this->addListen('UPDATE', 'edited_channel_post', $action);
    }

    public function onInlineQuery(Closure|array|string $action)
    {
        return $this->addListen('UPDATE', 'inline_query', $action);
    }

    public function onChosenInlineResult(Closure|array|string $action)
    {
        return $this->addListen('UPDATE', 'chosen_inline_result', $action);
    }

    public function onCallbackQuery(Closure|array|string $action)
    {
        return $this->addListen('CALLBACK_DATA', '{callbackQueryPlaceholder}', $action)
            ->where('callbackQueryPlaceholder', '.*');
    }

    public function onShippingQuery(Closure|array|string $action)
    {
        return $this->addListen('UPDATE', 'shipping_query', $action);
    }

    public function onPreCheckoutQuery(Closure|array|string $action)
    {
        return $this->addListen('UPDATE', 'pre_checkout_query', $action);
    }

    public function onPollAnswer(Closure|array|string $action)
    {
        return $this->addListen('UPDATE', 'poll_answer', $action);
    }

    public function onMyChatMember(Closure|array|string $action)
    {
        return $this->addListen('UPDATE', 'my_chat_member', $action);
    }

    public function onChatMember(Closure|array|string $action)
    {
        return $this->addListen('UPDATE', 'chat_member', $action);
    }

    public function onChatJoinRequest(Closure|array|string $action)
    {
        return $this->addListen('UPDATE', 'chat_join_request', $action);
    }

    public function onCallbackQueryData(array|string $pattern, Closure|array|string $action)
    {
        return $this->addListen('CALLBACK_DATA', $pattern, $action);
    }

    public function onMessageType(array|string $type, Closure|array|string $action)
    {
        $type = is_array($type) ? implode('|', $type) : $type;
        return $this->addListen(['MESSAGE', 'MESSAGE_TYPE'], $type, $action);
    }

    public function onReferral(string $pattern, Closure|array|string $action)
    {
        return $this->addListen('REFERRAL', $pattern, $action);
    }

    public function onHashtag(Closure|array|string $action)
    {
        return $this->addListen(['TEXT', 'ENTITIES'], 'hashtag', $action);
    }

    public function onCashtag(Closure|array|string $action)
    {
        return $this->addListen(['TEXT', 'ENTITIES'], 'cashtag', $action);
    }

    public function onMention(Closure|array|string $action)
    {
        return $this->addListen(['TEXT', 'ENTITIES'], 'mention', $action);
    }

    public function onAddMember(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'add_member', $action);
    }

    public function onJoinMember(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'join_member', $action);
    }
}
