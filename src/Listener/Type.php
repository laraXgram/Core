<?php

namespace LaraGram\Listener;

enum Type: string
{
    case TEXT = 'text';
    case COMMAND = 'command';
    case DICE = 'dice';
    case MEDIA = 'media';
    case UPDATE = 'update';
    case MESSAGE = 'message';
    case MESSAGE_TYPE = 'message_type';
    case CALLBACK_DATA = 'callback_query_data';
    case REFERRAL = 'referral';
    case ANY = 'any';

    const TYPES = [
        'text' => ['text'],
        'command' => ['command'],
        'dice' => ['dice'],
        'media' => [
            'voice', 'video_note', 'video', 'sticker',
            'photo', 'document', 'audio', 'animation'
        ],
        'update' => [
            'message', 'edited_message',
            'channel_post', 'edited_channel_post',
            'inline_query', 'chosen_inline_result',
            'callback_query', 'shipping_query',
            'pre_checkout_query', 'poll_answer',
            'my_chat_member', 'chat_member', 'chat_join_request'
        ],
        'message' => [
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
            'video_chat_participants_invited', 'web_app_data'
        ],
        'message_type' => ['message_type'],
        'callback_query_data' => ['callback_query_data'],
        'referral' => ['referral'],
        'any' => ['any'],
    ];

    public static function findType(string $value): ?self
    {
        foreach (self::TYPES as $type => $items) {
            if (in_array($value, $items)) {
                return self::from($type);
            }
        }

        return null;
    }
}