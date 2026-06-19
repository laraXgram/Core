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
        return $this->scopedContainer('message', $action);
    }

    public function onEditedMessage(Closure|array|string $action)
    {
        return $this->scopedContainer('edited_message', $action);
    }

    public function onChannelPost(Closure|array|string $action)
    {
        return $this->scopedContainer('channel_post', $action);
    }

    public function onEditedChannelPost(Closure|array|string $action)
    {
        return $this->scopedContainer('edited_channel_post', $action);
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
        return $this->addListen(['TEXT', 'MESSAGE', 'ENTITIES'], 'hashtag', $action);
    }

    public function onCashtag(Closure|array|string $action)
    {
        return $this->addListen(['TEXT', 'MESSAGE', 'ENTITIES'], 'cashtag', $action);
    }

    public function onMention(Closure|array|string $action)
    {
        return $this->addListen(['TEXT', 'MESSAGE', 'ENTITIES'], 'mention', $action);
    }

    public function onAddMember(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'add_member', $action);
    }

    public function onJoinMember(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'join_member', $action);
    }

    public function onBusinessConnection(Closure|array|string $action)
    {
        return $this->addListen('UPDATE', 'business_connection', $action);
    }

    public function onDeletedBusinessMessages(Closure|array|string $action)
    {
        return $this->addListen('UPDATE', 'deleted_business_messages', $action);
    }

    public function onMessageReaction(Closure|array|string $action)
    {
        return $this->addListen('UPDATE', 'message_reaction', $action);
    }

    public function onMessageReactionCount(Closure|array|string $action)
    {
        return $this->addListen('UPDATE', 'message_reaction_count', $action);
    }

    public function onMessageReactionEmoji(Closure|array|string $action, string|array $emoji = 'any')
    {
        $emoji = is_array($emoji) ? implode('|', $emoji) : $emoji;
        return $this->addListen(['UPDATE', 'REACTION'], "emoji::{$emoji}", $action);
    }

    public function onMessageReactionCustomEmoji(Closure|array|string $action)
    {
        return $this->addListen(['UPDATE', 'REACTION'], 'custom_emoji::', $action);
    }

    public function onMessageReactionType(string|array $type, Closure|array|string $action)
    {
        $type = is_array($type) ? implode('|', $type) : $type;
        return $this->addListen(['UPDATE', 'REACTION'], "type::{$type}", $action);
    }

    public function onPurchasedPaidMedia(Closure|array|string $action)
    {
        return $this->addListen('UPDATE', 'purchased_paid_media', $action);
    }

    public function onChatBoost(Closure|array|string $action)
    {
        return $this->addListen('UPDATE', 'chat_boost', $action);
    }

    public function onRemovedChatBoost(Closure|array|string $action)
    {
        return $this->addListen('UPDATE', 'removed_chat_boost', $action);
    }

    public function onManagedBot(Closure|array|string $action)
    {
        return $this->addListen('UPDATE', 'managed_bot', $action);
    }

    public function onPollUpdate(Closure|array|string $action)
    {
        return $this->addListen('UPDATE', 'poll', $action);
    }

    public function onAlbum(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'media_group_id', $action);
    }

    public function onInlineQueryQuery(array|string $pattern, Closure|array|string $action)
    {
        return $this->addListen(['UPDATE', 'QUERY'], $pattern, $action);
    }

    public function onChosenInlineResultQuery(array|string $pattern, Closure|array|string $action)
    {
        return $this->addListen(['UPDATE', 'QUERY'], $pattern, $action);
    }

    public function onBusinessMessage(Closure|array|string $action)
    {
        return $this->scopedContainer('business_message', $action);
    }

    public function onEditedBusinessMessage(Closure|array|string $action)
    {
        return $this->scopedContainer('edited_business_message', $action);
    }

    public function onGuestMessage(Closure|array|string $action)
    {
        return $this->scopedContainer('guest_message', $action);
    }

    public function onBusinessMessageText(string $pattern, Closure|array|string $action)
    {
        return $this->scopedContainerText('business_message', $pattern, $action);
    }

    public function onEditedBusinessMessageText(string $pattern, Closure|array|string $action)
    {
        return $this->scopedContainerText('edited_business_message', $pattern, $action);
    }

    public function onGuestMessageText(string $pattern, Closure|array|string $action)
    {
        return $this->scopedContainerText('guest_message', $pattern, $action);
    }

    public function onEditedMessageText(string $pattern, Closure|array|string $action)
    {
        return $this->scopedContainerText('edited_message', $pattern, $action);
    }

    public function onChannelPostCaption(string $pattern, Closure|array|string $action)
    {
        return $this->scopedContainerText('channel_post', $pattern, $action);
    }

    public function onEditedChannelPostCaption(string $pattern, Closure|array|string $action)
    {
        return $this->scopedContainerText('edited_channel_post', $pattern, $action);
    }

    protected function scopedContainer(string $container, Closure|array|string $action)
    {
        $tag = 'ANY_' . strtoupper($container);
        $placeholder = 'c_' . substr(md5($container), 0, 8);

        return $this->addListen(
            ['TEXT', 'MESSAGE', 'DICE', 'MESSAGE_TYPE', 'COMMAND', 'REFERRAL', $tag],
            "{{$placeholder}}",
            $action
        )->where($placeholder, '.*');
    }

    protected function scopedContainerText(string $container, string $pattern, Closure|array|string $action)
    {
        $tag = 'TEXT_' . strtoupper($container);
        return $this->addListen(['TEXT', $tag], $pattern, $action);
    }

    public function onContact(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'contact', $action);
    }

    public function onStory(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'story', $action);
    }

    public function onPaidMedia(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'paid_media', $action);
    }

    public function onLivePhoto(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'live_photo', $action);
    }

    public function onChecklist(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'checklist', $action);
    }

    public function onChecklistTasksDone(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'checklist_tasks_done', $action);
    }

    public function onChecklistTasksAdded(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'checklist_tasks_added', $action);
    }

    public function onBoostAdded(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'boost_added', $action);
    }

    public function onChatBackgroundSet(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'chat_background_set', $action);
    }

    public function onChannelChatCreated(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'channel_chat_created', $action);
    }

    public function onGift(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'gift', $action);
    }

    public function onUniqueGift(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'unique_gift', $action);
    }

    public function onGiftUpgradeSent(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'gift_upgrade_sent', $action);
    }

    public function onRefundedPayment(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'refunded_payment', $action);
    }

    public function onUsersShared(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'users_shared', $action);
    }

    public function onChatShared(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'chat_shared', $action);
    }

    public function onWriteAccessAllowed(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'write_access_allowed', $action);
    }

    public function onGiveawayCreated(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'giveaway_created', $action);
    }

    public function onGiveaway(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'giveaway', $action);
    }

    public function onGiveawayWinners(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'giveaway_winners', $action);
    }

    public function onGiveawayCompleted(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'giveaway_completed', $action);
    }

    public function onGeneralForumTopicHidden(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'general_forum_topic_hidden', $action);
    }

    public function onGeneralForumTopicUnhidden(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'general_forum_topic_unhidden', $action);
    }

    public function onDirectMessagePriceChanged(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'direct_message_price_changed', $action);
    }

    public function onPaidMessagePriceChanged(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'paid_message_price_changed', $action);
    }

    public function onPollOptionAdded(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'poll_option_added', $action);
    }

    public function onPollOptionDeleted(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'poll_option_deleted', $action);
    }

    public function onSuggestedPostApproved(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'suggested_post_approved', $action);
    }

    public function onSuggestedPostApprovalFailed(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'suggested_post_approval_failed', $action);
    }

    public function onSuggestedPostDeclined(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'suggested_post_declined', $action);
    }

    public function onSuggestedPostPaid(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'suggested_post_paid', $action);
    }

    public function onSuggestedPostRefunded(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'suggested_post_refunded', $action);
    }

    public function onManagedBotCreated(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'managed_bot_created', $action);
    }

    public function onChatOwnerLeft(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'chat_owner_left', $action);
    }

    public function onChatOwnerChanged(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'chat_owner_changed', $action);
    }

    public function onRichMessage(Closure|array|string $action)
    {
        return $this->addListen('MESSAGE', 'rich_message', $action);
    }

    public function onRichMessageType(string|array $type, Closure|array|string $action)
    {
        $type = is_array($type) ? implode('|', $type) : $type;
        return $this->addListen(['MESSAGE', 'RICH_TYPE'], $type, $action);
    }

    public function onUrl(Closure|array|string $action)
    {
        return $this->addListen(['TEXT', 'MESSAGE', 'ENTITIES'], 'url', $action);
    }

    public function onEmail(Closure|array|string $action)
    {
        return $this->addListen(['TEXT', 'MESSAGE', 'ENTITIES'], 'email', $action);
    }

    public function onPhoneNumber(Closure|array|string $action)
    {
        return $this->addListen(['TEXT', 'MESSAGE', 'ENTITIES'], 'phone_number', $action);
    }

    public function onTextLink(Closure|array|string $action)
    {
        return $this->addListen(['TEXT', 'MESSAGE', 'ENTITIES'], 'text_link', $action);
    }

    public function onTextMention(Closure|array|string $action)
    {
        return $this->addListen(['TEXT', 'MESSAGE', 'ENTITIES'], 'text_mention', $action);
    }

    public function onCustomEmoji(Closure|array|string $action)
    {
        return $this->addListen(['TEXT', 'MESSAGE', 'ENTITIES'], 'custom_emoji', $action);
    }

    public function onSpoiler(Closure|array|string $action)
    {
        return $this->addListen(['TEXT', 'MESSAGE', 'ENTITIES'], 'spoiler', $action);
    }

    public function onBlockquote(Closure|array|string $action)
    {
        return $this->addListen(['TEXT', 'MESSAGE', 'ENTITIES'], 'blockquote', $action);
    }

    public function onExpandableBlockquote(Closure|array|string $action)
    {
        return $this->addListen(['TEXT', 'MESSAGE', 'ENTITIES'], 'expandable_blockquote', $action);
    }

    public function onBold(Closure|array|string $action)
    {
        return $this->addListen(['TEXT', 'MESSAGE', 'ENTITIES'], 'bold', $action);
    }

    public function onItalic(Closure|array|string $action)
    {
        return $this->addListen(['TEXT', 'MESSAGE', 'ENTITIES'], 'italic', $action);
    }

    public function onUnderline(Closure|array|string $action)
    {
        return $this->addListen(['TEXT', 'MESSAGE', 'ENTITIES'], 'underline', $action);
    }

    public function onStrikethrough(Closure|array|string $action)
    {
        return $this->addListen(['TEXT', 'MESSAGE', 'ENTITIES'], 'strikethrough', $action);
    }

    public function onCode(Closure|array|string $action)
    {
        return $this->addListen(['TEXT', 'MESSAGE', 'ENTITIES'], 'code', $action);
    }

    public function onPre(Closure|array|string $action)
    {
        return $this->addListen(['TEXT', 'MESSAGE', 'ENTITIES'], 'pre', $action);
    }

    public function onStep(string $step, Closure|array|string $action, ?string $pattern = null, array|string|null $method = null)
    {
        $placeholder = 's_' . substr(md5($step), 0, 8);

        if ($method === '*') {
            $methods = Listener::$verbs;
        } elseif ($method !== null) {
            $methods = array_map('strtoupper', (array) $method);
        } else {
            $methods = ['TEXT'];
        }

        $listenPattern = $pattern ?? "{{$placeholder}}";

        $listen = $this->addListen($methods, $listenPattern, $action);
        $listen->setStepName($step);

        if ($pattern === null) {
            $listen->where($placeholder, '.*');
        }

        return $listen;
    }
}
