<?php

namespace LaraGram\Request;

use LaraGram\Laraquest\Exceptions\InvalidUpdateType;

trait InteractWithUpdate
{
    /**
     * This function returns the type of the update.
     *
     * @return false|string
     * @throws InvalidUpdateType
     */
    public function getUpdateType(): false|string
    {
        return match (true) {
            isset($this->message) => $this->getUpdateMessageSubType($this->message),
            isset($this->edited_message) => $this->getUpdateMessageSubType($this->edited_message),
            isset($this->channel_post) => $this->getUpdateMessageSubType($this->channel_post),
            isset($this->edited_channel_post) => $this->getUpdateMessageSubType($this->edited_channel_post),
            isset($this->business_message) => $this->getUpdateMessageSubType($this->business_message),
            isset($this->edited_business_message) => $this->getUpdateMessageSubType($this->edited_business_message),
            isset($this->business_connection) => 'business_connection',
            isset($this->deleted_business_messages) => 'deleted_business_messages',
            isset($this->message_reaction) => 'message_reaction',
            isset($this->message_reaction_count) => 'message_reaction_count',
            isset($this->inline_query) => 'inline_query',
            isset($this->chosen_inline_result) => 'chosen_inline_result',
            isset($this->callback_query) => 'callback_query',
            isset($this->shipping_query) => 'shipping_query',
            isset($this->pre_checkout_query) => 'pre_checkout_query',
            isset($this->purchased_paid_media) => 'purchased_paid_media',
            isset($this->poll) => 'poll',
            isset($this->poll_answer) => 'poll_answer',
            isset($this->my_chat_member) => 'my_chat_member',
            isset($this->chat_member) => 'chat_member',
            isset($this->chat_join_request) => 'chat_join_request',
            isset($this->chat_boost) => 'chat_boost',
            isset($this->removed_chat_boost) => 'removed_chat_boost',
            default => false
        };
    }

    /**
     * This function returns the type of the message.
     *
     * @param  \LaraGram\Laraquest\Updates\Message|object $message
     * @return string
     */
    public function getUpdateMessageSubType(object $message): string
    {
        return match (true) {
            isset($message->text) => 'text',
            isset($message->animation) => 'animation',
            isset($message->audio) => 'audio',
            isset($message->document) => 'document',
            isset($message->paid_media) => 'paid_media',
            isset($message->photo) => 'photo',
            isset($message->sticker) => 'sticker',
            isset($message->story) => 'story',
            isset($message->video) => 'video',
            isset($message->video_note) => 'video_note',
            isset($message->voice) => 'voice',
            isset($message->checklist) => 'checklist',
            isset($message->contact) => 'contact',
            isset($message->dice) => 'dice',
            isset($message->game) => 'game',
            isset($message->poll) => 'poll',
            isset($message->venue) => 'venue',
            isset($message->location) => 'location',
            isset($message->pinned_message) => 'pinned_message',
            isset($message->invoice) => 'invoice',
            isset($message->successful_payment) => 'successful_payment',
            isset($message->refunded_payment) => 'refunded_payment',
            isset($message->users_shared) => 'users_shared',
            isset($message->chat_shared) => 'chat_shared',
            isset($message->passport_data) => 'passport_data',
            isset($message->proximity_alert_triggered) => 'proximity_alert_triggered',
            isset($message->boost_added) => 'boost_added',
            isset($message->chat_background_set) => 'chat_background_set',
            isset($message->checklist_tasks_done) => 'checklist_tasks_done',
            isset($message->checklist_tasks_added) => 'checklist_tasks_added',
            isset($message->direct_message_price_changed) => 'direct_message_price_changed',
            isset($message->forum_topic_created) => 'forum_topic_created',
            isset($message->forum_topic_edited) => 'forum_topic_edited',
            isset($message->forum_topic_closed) => 'forum_topic_closed',
            isset($message->forum_topic_reopened) => 'forum_topic_reopened',
            isset($message->general_forum_topic_hidden) => 'general_forum_topic_hidden',
            isset($message->general_forum_topic_unhidden) => 'general_forum_topic_unhidden',
            isset($message->giveaway_created) => 'giveaway_created',
            isset($message->giveaway) => 'giveaway',
            isset($message->giveaway_winners) => 'giveaway_winners',
            isset($message->giveaway_completed) => 'giveaway_completed',
            isset($message->paid_message_price_changed) => 'paid_message_price_changed',
            isset($message->video_chat_scheduled) => 'video_chat_scheduled',
            isset($message->video_chat_started) => 'video_chat_started',
            isset($message->video_chat_ended) => 'video_chat_ended',
            isset($message->video_chat_participants_invited) => 'video_chat_participants_invited',
            isset($message->left_chat_member) => 'left_chat_member',
            isset($message->new_chat_members) => 'new_chat_members',
            isset($message->new_chat_title) => 'new_chat_title',
            isset($message->new_chat_photo) => 'new_chat_photo',
            isset($message->delete_chat_photo) => 'delete_chat_photo',
            isset($message->group_chat_created) => 'group_chat_created',
            isset($message->supergroup_chat_created) => 'supergroup_chat_created',
            isset($message->channel_chat_created) => 'channel_chat_created',
            isset($message->message_auto_delete_timer_changed) => 'message_auto_delete_timer_changed',
            default => throw new InvalidUpdateType('Unknown message type')
        };
    }

    /**
     * detect the scope of message.
     *
     * @return string
     */
    public function scope()
    {
        return match (true) {
            isset($this->message->chat->type) => $this->message->chat->type,
            isset($this->edited_message->chat->type) => $this->edited_message->chat->type,
            isset($this->channel_post->chat->type) => $this->channel_post->chat->type,
            isset($this->edited_channel_post->chat->type) => $this->edited_channel_post->chat->type,
            isset($this->business_message->chat->type) => $this->business_message->chat->type,
            isset($this->edited_business_message->chat->type) => $this->edited_business_message->chat->type,
            isset($this->deleted_business_messages->chat->type) => $this->deleted_business_messages->chat->type,
            isset($this->message_reaction->chat->type) => $this->message_reaction->chat->type,
            isset($this->message_reaction_count->chat->type) => $this->message_reaction_count->chat->type,
            isset($this->callback_query->message->chat->type) => $this->callback_query->message->chat->type,
            isset($this->poll_answer->voter_chat->type) => $this->poll_answer->voter_chat->type,
            isset($this->my_chat_member->chat->type) => $this->my_chat_member->chat->type,
            isset($this->chat_member->chat->type) => $this->chat_member->chat->type,
            isset($this->chat_join_request->chat->type) => $this->chat_join_request->chat->type,
            isset($this->chat_boost->chat->type) => $this->chat_boost->chat->type,
            isset($this->removed_chat_boost->chat->type) => $this->removed_chat_boost->chat->type,
            default => null
        };
    }

    /**
     * detect the message is reply or not.
     *
     * @return bool
     */
    public function isReply()
    {
        return match (true) {
            isset($this->message->reply_to_message),
            isset($this->edited_message->reply_to_message),
            isset($this->channel_post->reply_to_message),
            isset($this->edited_channel_post->reply_to_message),
            isset($this->business_message->reply_to_message),
            isset($this->edited_business_message->reply_to_message),
            isset($this->callback_query->message->reply_to_message) => true,
            default => false
        };
    }
}
