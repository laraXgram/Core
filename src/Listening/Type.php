<?php

namespace LaraGram\Listening;

enum Type: string
{
    case TEXT = 'text';
    case DICE = 'dice';
    case UPDATE = 'update';
    case MESSAGE = 'message';

    const TYPES = [
        'text' => ['text'],
        'dice' => ['dice'],
        'update' => [
            'message', 'edited_message',
            'channel_post', 'edited_channel_post',
            'inline_query', 'chosen_inline_result',
            'callback_query', 'shipping_query',
            'pre_checkout_query', 'poll_answer',
            'my_chat_member', 'chat_member', 'chat_join_request',
            'business_connection', 'deleted_business_messages',
            'message_reaction', 'message_reaction_count',
            'purchased_paid_media', 'chat_boost', 'removed_chat_boost',
            'managed_bot',
        ],
        'message' => [
            'voice', 'video_note', 'video', 'sticker',
            'photo', 'document', 'audio', 'animation',
            'game', 'poll', 'venue', 'location',
            'new_chat_members', 'left_chat_member',
            'new_chat_title', 'new_chat_photo',
            'delete_chat_photo', 'group_chat_created',
            'supergroup_chat_created', 'message_auto_delete_timer_changed',
            'migrate_to_chat_id', 'migrate_from_chat_id',
            'pinned_message', 'invoice', 'successful_payment',
            'connected_website', 'passport_data', 'proximity_alert_triggered',
            'forum_topic_created', 'forum_topic_edited', 'forum_topic_closed',
            'forum_topic_reopened', 'video_chat_scheduled',
            'video_chat_started', 'video_chat_ended',
            'video_chat_participants_invited', 'web_app_data',
            'contact', 'story', 'paid_media', 'live_photo',
            'checklist', 'checklist_tasks_done', 'checklist_tasks_added',
            'boost_added', 'chat_background_set', 'channel_chat_created',
            'gift', 'unique_gift', 'gift_upgrade_sent', 'rich_message',
            'refunded_payment', 'users_shared', 'chat_shared',
            'write_access_allowed', 'giveaway_created', 'giveaway',
            'giveaway_winners', 'giveaway_completed',
            'general_forum_topic_hidden', 'general_forum_topic_unhidden',
            'direct_message_price_changed', 'paid_message_price_changed',
            'poll_option_added', 'poll_option_deleted',
            'suggested_post_approved', 'suggested_post_approval_failed',
            'suggested_post_declined', 'suggested_post_paid',
            'suggested_post_refunded', 'managed_bot_created',
            'chat_owner_left', 'chat_owner_changed',
        ],
    ];

    public static function findVerb(string $value): ?self
    {
        foreach (self::TYPES as $type => $items) {
            if (in_array($value, $items)) {
                return self::from($type);
            }
        }

        return null;
    }
}