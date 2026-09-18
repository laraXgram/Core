<?php

// schema: 9037dc743caa4ad82e35cafbb9b0804e

namespace LaraGram\Broadcasting\Telegram\Concerns;

use LaraGram\Broadcasting\Telegram\TelegramBroadcast;

/**
 * @generated
 *
 * The Bot API methods that target a chat or a user, for Telegram broadcasts.
 *
 * Each method takes Laraquest's parameters, without the recipient (chat_id,
 * or user_id for methods that have no chat_id), which the broadcast fills in
 * for every recipient, and returns the broadcast so only its delivery
 * methods can follow.
 *
 * Generated from the Laraquest Bot API schema. Changes are overwritten when
 * the schema changes.
 */
trait BroadcastsBotApiMethods
{
    /**
     * Use this method to add a new sticker to a set created by the bot. Emoji sticker sets can have up to 200 stickers. Other sticker sets can have up to 120 stickers. Returns True on success.
     *
     * @param  array  $sticker  A JSON-serialized object with information about the added sticker. If exactly the same sticker had already been added to the set, then the set isn't changed.
     * @param  string  $name  Sticker set name
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function addStickerToSet($sticker, $name): TelegramBroadcast
    {
        return $this->endpoint('addStickerToSet', get_defined_vars(), 'user_id');
    }

    /**
     * Use this method to approve a chat join request. The bot must be an administrator in the chat for this to work and must have the can_invite_users administrator right. Returns True on success.
     *
     * @param  int|string  $user_id  Unique identifier of the target user
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function approveChatJoinRequest($user_id): TelegramBroadcast
    {
        return $this->endpoint('approveChatJoinRequest', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to approve a suggested post in a direct messages chat. The bot must have the 'can_post_messages' administrator right in the corresponding channel chat. Returns True on success.
     *
     * @param  int|string  $message_id  Identifier of a suggested post message to approve
     * @param  int|string  $send_date  Point in time (Unix timestamp) when the post is expected to be published; omit if the date has already been specified when the suggested post was created. If specified, then the date must be not more than 2678400 seconds (30 days) in the future.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function approveSuggestedPost($message_id, $send_date = null): TelegramBroadcast
    {
        return $this->endpoint('approveSuggestedPost', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to ban a user in a group, a supergroup or a channel. In the case of supergroups and channels, the user will not be able to return to the chat on their own using invite links, etc., unless unbanned first. The bot must be an administrator in the chat for this to work and must have the appropriate administrator rights. Returns True on success.
     *
     * @param  int|string  $user_id  Unique identifier of the target user
     * @param  int|string  $until_date  Date when the user will be unbanned; Unix time. If user is banned for more than 366 days or less than 30 seconds from the current time they are considered to be banned forever. Applied for supergroups and channels only.
     * @param  bool  $revoke_messages  Pass True to delete all messages from the chat for the user that is being removed. If False, the user will be able to see messages in the group that were sent before the user was removed. Always True for supergroups and channels.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function banChatMember($user_id, $until_date = null, $revoke_messages = null): TelegramBroadcast
    {
        return $this->endpoint('banChatMember', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to ban a channel chat in a supergroup or a channel. Until the chat is unbanned, the owner of the banned chat won't be able to send messages on behalf of any of their channels. The bot must be an administrator in the supergroup or channel for this to work and must have the appropriate administrator rights. Returns True on success.
     *
     * @param  int|string  $sender_chat_id  Unique identifier of the target sender chat
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function banChatSenderChat($sender_chat_id): TelegramBroadcast
    {
        return $this->endpoint('banChatSenderChat', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to close an open topic in a forum supergroup chat. The bot must be an administrator in the chat for this to work and must have the can_manage_topics administrator rights, unless it is the creator of the topic. Returns True on success.
     *
     * @param  int|string  $message_thread_id  Unique identifier for the target message thread of the forum topic
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function closeForumTopic($message_thread_id): TelegramBroadcast
    {
        return $this->endpoint('closeForumTopic', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to close an open 'General' topic in a forum supergroup chat. The bot must be an administrator in the chat for this to work and must have the can_manage_topics administrator rights. Returns True on success.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function closeGeneralForumTopic(): TelegramBroadcast
    {
        return $this->endpoint('closeGeneralForumTopic', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to copy messages of any kind. Service messages, paid media messages, giveaway messages, giveaway winners messages, and invoice messages can't be copied. A quiz poll can be copied only if the value of the field correct_option_ids is known to the bot. The method is analogous to the method forwardMessage, but the copied message doesn't have a link to the original message. Returns the MessageId of the sent message on success.
     *
     * @param  int|string  $from_chat_id  Unique identifier for the chat where the original message was sent (or username of the target bot, supergroup or channel in the format @username)
     * @param  int|string  $message_id  Message identifier in the chat specified in from_chat_id
     * @param  string  $caption  New caption for media, 0-1024 characters after entities parsing. If not specified, the original caption is kept.
     * @param  string  $parse_mode  Mode for parsing entities in the new caption. See formatting options for more details.
     * @param  array  $reply_markup  Additional interface options. A JSON-serialized object for an inline keyboard, custom reply keyboard, instructions to remove a reply keyboard or to force a reply from the user.
     * @param  int|string  $message_thread_id  Unique identifier for the target message thread (topic) of a forum; for forum supergroups and private chats of bots with forum topic mode enabled only
     * @param  int|string  $direct_messages_topic_id  Identifier of the direct messages topic to which the message will be sent; required if the message is sent to a direct messages chat
     * @param  int|string  $video_start_timestamp  New start timestamp for the copied video in the message
     * @param  \LaraGram\Laraquest\Updates\MessageEntity[]|array|string  $caption_entities  A JSON-serialized list of special entities that appear in the new caption, which can be specified instead of parse_mode
     * @param  bool  $show_caption_above_media  Pass True if the caption must be shown above the message media. Ignored if a new caption isn't specified.
     * @param  bool  $disable_notification  Sends the message silently. Users will receive a notification with no sound.
     * @param  bool  $protect_content  Protects the contents of the sent message from forwarding and saving
     * @param  bool  $allow_paid_broadcast  Pass True to allow up to 1000 messages per second, ignoring broadcasting limits for a fee of 0.1 Telegram Stars per message. The relevant Stars will be withdrawn from the bot's balance.
     * @param  string  $message_effect_id  Unique identifier of the message effect to be added to the message; only available when copying to private chats
     * @param  array  $suggested_post_parameters  A JSON-serialized object containing the parameters of the suggested post to send; for direct messages chats only. If the message is sent as a reply to another suggested post, then that suggested post is automatically declined.
     * @param  array  $reply_parameters  Description of the message to reply to
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function copyMessage($from_chat_id, $message_id, $caption = null, $parse_mode = null, $reply_markup = null, $message_thread_id = null, $direct_messages_topic_id = null, $video_start_timestamp = null, $caption_entities = null, $show_caption_above_media = null, $disable_notification = null, $protect_content = null, $allow_paid_broadcast = null, $message_effect_id = null, $suggested_post_parameters = null, $reply_parameters = null): TelegramBroadcast
    {
        return $this->endpoint('copyMessage', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to copy messages of any kind. If some of the specified messages can't be found or copied, they are skipped. Service messages, paid media messages, giveaway messages, giveaway winners messages, and invoice messages can't be copied. A quiz poll can be copied only if the value of the field correct_option_ids is known to the bot. The method is analogous to the method forwardMessages, but the copied messages don't have a link to the original message. Album grouping is kept for copied messages. On success, an Array of MessageId of the sent messages is returned.
     *
     * @param  int|string  $from_chat_id  Unique identifier for the chat where the original messages were sent (or username of the target bot, supergroup or channel in the format @username)
     * @param  int[]  $message_ids  A JSON-serialized list of 1-100 identifiers of messages in the chat from_chat_id to copy. The identifiers must be specified in a strictly increasing order.
     * @param  int|string  $message_thread_id  Unique identifier for the target message thread (topic) of a forum; for forum supergroups and private chats of bots with forum topic mode enabled only
     * @param  int|string  $direct_messages_topic_id  Identifier of the direct messages topic to which the messages will be sent; required if the messages are sent to a direct messages chat
     * @param  bool  $disable_notification  Sends the messages silently. Users will receive a notification with no sound.
     * @param  bool  $protect_content  Protects the contents of the sent messages from forwarding and saving
     * @param  bool  $remove_caption  Pass True to copy the messages without their captions
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function copyMessages($from_chat_id, $message_ids, $message_thread_id = null, $direct_messages_topic_id = null, $disable_notification = null, $protect_content = null, $remove_caption = null): TelegramBroadcast
    {
        return $this->endpoint('copyMessages', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to create an additional invite link for a chat. The bot must be an administrator in the chat for this to work and must have the appropriate administrator rights. The link can be revoked using the method revokeChatInviteLink. Returns the new invite link as ChatInviteLink object.
     *
     * @param  string  $name  Invite link name; 0-32 characters
     * @param  int|string  $expire_date  Point in time (Unix timestamp) when the link will expire
     * @param  int|string  $member_limit  The maximum number of users that can be members of the chat simultaneously after joining the chat via this invite link; 1-99999
     * @param  bool  $creates_join_request  True, if users joining the chat via the link need to be approved by chat administrators. If True, member_limit can't be specified.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function createChatInviteLink($name = null, $expire_date = null, $member_limit = null, $creates_join_request = null): TelegramBroadcast
    {
        return $this->endpoint('createChatInviteLink', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to create a subscription invite link for a channel chat. The bot must have the can_invite_users administrator rights. The link can be edited using the method editChatSubscriptionInviteLink or revoked using the method revokeChatInviteLink. Returns the new invite link as a ChatInviteLink object.
     *
     * @param  int|string  $subscription_period  The number of seconds the subscription will be active for before the next payment. Currently, it must always be 2592000 (30 days).
     * @param  int|string  $subscription_price  The amount of Telegram Stars a user must pay initially and after each subsequent subscription period to be a member of the chat; 1-10000
     * @param  string  $name  Invite link name; 0-32 characters
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function createChatSubscriptionInviteLink($subscription_period, $subscription_price, $name = null): TelegramBroadcast
    {
        return $this->endpoint('createChatSubscriptionInviteLink', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to create a topic in a forum supergroup chat or a private chat with a user. In the case of a supergroup chat the bot must be an administrator in the chat for this to work and must have the can_manage_topics administrator right. Returns information about the created topic as a ForumTopic object.
     *
     * @param  string  $name  Topic name, 1-128 characters
     * @param  int|string  $icon_color  Color of the topic icon in RGB format. Currently, must be one of 7322096 (0x6FB9F0), 16766590 (0xFFD67E), 13338331 (0xCB86DB), 9367192 (0x8EEE98), 16749490 (0xFF93B2), or 16478047 (0xFB6F5F).
     * @param  string  $icon_custom_emoji_id  Unique identifier of the custom emoji shown as the topic icon. Use getForumTopicIconStickers to get all allowed custom emoji identifiers.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function createForumTopic($name, $icon_color = null, $icon_custom_emoji_id = null): TelegramBroadcast
    {
        return $this->endpoint('createForumTopic', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to create a new sticker set owned by a user. The bot will be able to edit the sticker set thus created. Returns True on success.
     *
     * @param  string  $name  Short name of sticker set, to be used in t.me/addstickers/ URLs (e.g., animals). Can contain only English letters, digits and underscores. Must begin with a letter, can't contain consecutive underscores and must end in "_by_<bot_username>". <bot_username> is case insensitive. 1-64 characters.
     * @param  string  $title  Sticker set title, 1-64 characters
     * @param  \LaraGram\Laraquest\Updates\InputSticker[]|array|string  $stickers  A JSON-serialized list of 1-50 initial stickers to be added to the sticker set
     * @param  string  $sticker_type  Type of stickers in the set, pass “regular”, “mask”, or “custom_emoji”. By default, a regular sticker set is created.
     * @param  bool  $needs_repainting  Pass True if stickers in the sticker set must be repainted to the color of text when used in messages, the accent color if used as emoji status, white on chat photos, or another appropriate color based on context; for custom emoji sticker sets only
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function createNewStickerSet($name, $title, $stickers, $sticker_type = null, $needs_repainting = null): TelegramBroadcast
    {
        return $this->endpoint('createNewStickerSet', get_defined_vars(), 'user_id');
    }

    /**
     * Use this method to decline a chat join request. The bot must be an administrator in the chat for this to work and must have the can_invite_users administrator right. Returns True on success.
     *
     * @param  int|string  $user_id  Unique identifier of the target user
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function declineChatJoinRequest($user_id): TelegramBroadcast
    {
        return $this->endpoint('declineChatJoinRequest', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to decline a suggested post in a direct messages chat. The bot must have the 'can_manage_direct_messages' administrator right in the corresponding channel chat. Returns True on success.
     *
     * @param  int|string  $message_id  Identifier of a suggested post message to decline
     * @param  string  $comment  Comment for the creator of the suggested post; 0-128 characters
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function declineSuggestedPost($message_id, $comment = null): TelegramBroadcast
    {
        return $this->endpoint('declineSuggestedPost', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to remove up to 10000 recent reactions in a group or a supergroup chat added by a given user or chat. The bot must have the 'can_delete_messages' administrator right in the chat. Returns True on success.
     *
     * @param  int|string  $user_id  Identifier of the user whose reactions will be removed, if the reactions were added by a user
     * @param  int|string  $actor_chat_id  Identifier of the chat whose reactions will be removed, if the reactions were added by a chat
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function deleteAllMessageReactions($user_id = null, $actor_chat_id = null): TelegramBroadcast
    {
        return $this->endpoint('deleteAllMessageReactions', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to delete a chat photo. Photos can't be changed for private chats. The bot must be an administrator in the chat for this to work and must have the appropriate administrator rights. Returns True on success.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function deleteChatPhoto(): TelegramBroadcast
    {
        return $this->endpoint('deleteChatPhoto', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to delete a group sticker set from a supergroup. The bot must be an administrator in the chat for this to work and must have the appropriate administrator rights. Use the field can_set_sticker_set optionally returned in getChat requests to check if the bot can use this method. Returns True on success.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function deleteChatStickerSet(): TelegramBroadcast
    {
        return $this->endpoint('deleteChatStickerSet', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to delete an ephemeral message. Note that it is not guaranteed that the user will receive the message deletion event, especially if they are offline. Returns True on success.
     *
     * @param  int|string  $receiver_user_id  Identifier of the user who received the message
     * @param  int|string  $ephemeral_message_id  Identifier of the ephemeral message to delete
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function deleteEphemeralMessage($receiver_user_id, $ephemeral_message_id): TelegramBroadcast
    {
        return $this->endpoint('deleteEphemeralMessage', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to delete a forum topic along with all its messages in a forum supergroup chat or a private chat with a user. In the case of a supergroup chat the bot must be an administrator in the chat for this to work and must have the can_delete_messages administrator rights. Returns True on success.
     *
     * @param  int|string  $message_thread_id  Unique identifier for the target message thread of the forum topic
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function deleteForumTopic($message_thread_id): TelegramBroadcast
    {
        return $this->endpoint('deleteForumTopic', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to delete a message, including service messages, with the following limitations:- A message can only be deleted if it was sent less than 48 hours ago.- Service messages about a supergroup, channel, or forum topic creation can't be deleted.- A dice message in a private chat can only be deleted if it was sent more than 24 hours ago.- Bots can delete outgoing messages in private chats, groups, and supergroups.- Bots can delete incoming messages in private chats.- Bots granted can_post_messages permissions can delete outgoing messages in channels.- If the bot is an administrator of a group, it can delete any message there.- If the bot has can_delete_messages administrator right in a supergroup or a channel, it can delete any message there.- If the bot has can_manage_direct_messages administrator right in a channel, it can delete any message in the corresponding direct messages chat.Returns True on success.
     *
     * @param  int|string  $message_id  Identifier of the message to delete
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function deleteMessage($message_id): TelegramBroadcast
    {
        return $this->endpoint('deleteMessage', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to remove a reaction from a message in a group or a supergroup chat. The bot must have the 'can_delete_messages' administrator right in the chat. Returns True on success.
     *
     * @param  int|string  $message_id  Identifier of the target message
     * @param  int|string  $user_id  Identifier of the user whose reaction will be removed, if the reaction was added by a user
     * @param  int|string  $actor_chat_id  Identifier of the chat whose reaction will be removed, if the reaction was added by a chat
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function deleteMessageReaction($message_id, $user_id = null, $actor_chat_id = null): TelegramBroadcast
    {
        return $this->endpoint('deleteMessageReaction', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to delete multiple messages simultaneously. If some of the specified messages can't be found, they are skipped. Returns True on success.
     *
     * @param  int[]  $message_ids  A JSON-serialized list of 1-100 identifiers of messages to delete. See deleteMessage for limitations on which messages can be deleted.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function deleteMessages($message_ids): TelegramBroadcast
    {
        return $this->endpoint('deleteMessages', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to edit a non-primary invite link created by the bot. The bot must be an administrator in the chat for this to work and must have the appropriate administrator rights. Returns the edited invite link as a ChatInviteLink object.
     *
     * @param  string  $invite_link  The invite link to edit
     * @param  string  $name  Invite link name; 0-32 characters
     * @param  int|string  $expire_date  Point in time (Unix timestamp) when the link will expire
     * @param  int|string  $member_limit  The maximum number of users that can be members of the chat simultaneously after joining the chat via this invite link; 1-99999
     * @param  bool  $creates_join_request  True, if users joining the chat via the link need to be approved by chat administrators. If True, member_limit can't be specified.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function editChatInviteLink($invite_link, $name = null, $expire_date = null, $member_limit = null, $creates_join_request = null): TelegramBroadcast
    {
        return $this->endpoint('editChatInviteLink', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to edit a subscription invite link created by the bot. The bot must have the can_invite_users administrator rights. Returns the edited invite link as a ChatInviteLink object.
     *
     * @param  string  $invite_link  The invite link to edit
     * @param  string  $name  Invite link name; 0-32 characters
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function editChatSubscriptionInviteLink($invite_link, $name = null): TelegramBroadcast
    {
        return $this->endpoint('editChatSubscriptionInviteLink', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to edit the caption of an ephemeral message. Note that it is not guaranteed that the user will receive the message edit event, especially if they are offline. On success, True is returned.
     *
     * @param  int|string  $receiver_user_id  Identifier of the user who received the message
     * @param  int|string  $ephemeral_message_id  Identifier of the ephemeral message to edit
     * @param  string  $caption  New caption of the message, 0-1024 characters after entities parsing
     * @param  string  $parse_mode  Mode for parsing entities in the message caption. See formatting options for more details.
     * @param  array  $reply_markup  A JSON-serialized object for an inline keyboard
     * @param  \LaraGram\Laraquest\Updates\MessageEntity[]|array|string  $caption_entities  A JSON-serialized list of special entities that appear in the caption, which can be specified instead of parse_mode
     * @param  bool  $show_caption_above_media  Pass True if the caption must be shown above the message media. Supported only for animation, photo and video messages.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function editEphemeralMessageCaption($receiver_user_id, $ephemeral_message_id, $caption = null, $parse_mode = null, $reply_markup = null, $caption_entities = null, $show_caption_above_media = null): TelegramBroadcast
    {
        return $this->endpoint('editEphemeralMessageCaption', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to edit the media of an ephemeral message. Note that it is not guaranteed that the user will receive the message edit event, especially if they are offline. On success, True is returned.
     *
     * @param  array  $media  A JSON-serialized object for the new media content of the message
     * @param  int|string  $receiver_user_id  Identifier of the user who received the message
     * @param  int|string  $ephemeral_message_id  Identifier of the ephemeral message to edit
     * @param  array  $reply_markup  A JSON-serialized object for an inline keyboard
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function editEphemeralMessageMedia($media, $receiver_user_id, $ephemeral_message_id, $reply_markup = null): TelegramBroadcast
    {
        return $this->endpoint('editEphemeralMessageMedia', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to edit only the reply markup of an ephemeral message. Note that it is not guaranteed that the user will receive the message edit event, especially if they are offline. On success, True is returned.
     *
     * @param  int|string  $receiver_user_id  Identifier of the user who received the message
     * @param  int|string  $ephemeral_message_id  Identifier of the ephemeral message to edit
     * @param  array  $reply_markup  A JSON-serialized object for an inline keyboard
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function editEphemeralMessageReplyMarkup($receiver_user_id, $ephemeral_message_id, $reply_markup = null): TelegramBroadcast
    {
        return $this->endpoint('editEphemeralMessageReplyMarkup', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to edit an ephemeral text or rich message. Note that it is not guaranteed that the user will receive the message edit event, especially if they are offline. On success, True is returned.
     *
     * @param  int|string  $receiver_user_id  Identifier of the user who received the message
     * @param  int|string  $ephemeral_message_id  Identifier of the ephemeral message to edit
     * @param  string  $text  New text of the message, 1-4096 characters after entity parsing; required if rich_message isn't specified
     * @param  string  $parse_mode  Mode for parsing entities in the message text. See formatting options for more details.
     * @param  array  $reply_markup  A JSON-serialized object for an inline keyboard
     * @param  \LaraGram\Laraquest\Updates\MessageEntity[]|array|string  $entities  A JSON-serialized list of special entities that appear in message text, which can be specified instead of parse_mode
     * @param  array  $rich_message  New rich content of the message; required if text isn't specified
     * @param  array  $link_preview_options  Link preview generation options for the message
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function editEphemeralMessageText($receiver_user_id, $ephemeral_message_id, $text = null, $parse_mode = null, $reply_markup = null, $entities = null, $rich_message = null, $link_preview_options = null): TelegramBroadcast
    {
        return $this->endpoint('editEphemeralMessageText', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to edit name and icon of a topic in a forum supergroup chat or a private chat with a user. In the case of a supergroup chat the bot must be an administrator in the chat for this to work and must have the can_manage_topics administrator rights, unless it is the creator of the topic. Returns True on success.
     *
     * @param  int|string  $message_thread_id  Unique identifier for the target message thread of the forum topic
     * @param  string  $name  New topic name, 0-128 characters. If not specified or empty, the current name of the topic will be kept.
     * @param  string  $icon_custom_emoji_id  New unique identifier of the custom emoji shown as the topic icon. Use getForumTopicIconStickers to get all allowed custom emoji identifiers. Pass an empty string to remove the icon. If not specified, the current icon will be kept.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function editForumTopic($message_thread_id, $name = null, $icon_custom_emoji_id = null): TelegramBroadcast
    {
        return $this->endpoint('editForumTopic', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to edit the name of the 'General' topic in a forum supergroup chat. The bot must be an administrator in the chat for this to work and must have the can_manage_topics administrator rights. Returns True on success.
     *
     * @param  string  $name  New topic name, 1-128 characters
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function editGeneralForumTopic($name): TelegramBroadcast
    {
        return $this->endpoint('editGeneralForumTopic', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to edit captions of messages. On success, if the edited message is not an inline message, the edited Message is returned, otherwise True is returned. Note that business messages that were not sent by the bot and do not contain an inline keyboard can only be edited within 48 hours from the time they were sent.
     *
     * @param  string  $caption  New caption of the message, 0-1024 characters after entities parsing
     * @param  int|string  $message_id  Required if inline_message_id is not specified. Identifier of the message to edit.
     * @param  string  $parse_mode  Mode for parsing entities in the message caption. See formatting options for more details.
     * @param  array  $reply_markup  A JSON-serialized object for an inline keyboard
     * @param  string  $business_connection_id  Unique identifier of the business connection on behalf of which the message to be edited was sent
     * @param  string  $inline_message_id  Required if chat_id and message_id are not specified. Identifier of the inline message.
     * @param  \LaraGram\Laraquest\Updates\MessageEntity[]|array|string  $caption_entities  A JSON-serialized list of special entities that appear in the caption, which can be specified instead of parse_mode
     * @param  bool  $show_caption_above_media  Pass True if the caption must be shown above the message media. Supported only for animation, photo and video messages.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function editMessageCaption($caption = null, $message_id = null, $parse_mode = null, $reply_markup = null, $business_connection_id = null, $inline_message_id = null, $caption_entities = null, $show_caption_above_media = null): TelegramBroadcast
    {
        return $this->endpoint('editMessageCaption', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to edit a checklist on behalf of a connected business account. On success, the edited Message is returned.
     *
     * @param  string  $business_connection_id  Unique identifier of the business connection on behalf of which the message will be sent
     * @param  int|string  $message_id  Unique identifier for the target message
     * @param  array  $checklist  A JSON-serialized object for the new checklist
     * @param  array  $reply_markup  A JSON-serialized object for the new inline keyboard for the message
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function editMessageChecklist($business_connection_id, $message_id, $checklist, $reply_markup = null): TelegramBroadcast
    {
        return $this->endpoint('editMessageChecklist', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to edit live location messages. A location can be edited until its live_period expires or editing is explicitly disabled by a call to stopMessageLiveLocation. On success, if the edited message is not an inline message, the edited Message is returned, otherwise True is returned.
     *
     * @param  float|string  $latitude  Latitude of new location
     * @param  float|string  $longitude  Longitude of new location
     * @param  int|string  $message_id  Required if inline_message_id is not specified. Identifier of the message to edit.
     * @param  array  $reply_markup  A JSON-serialized object for a new inline keyboard
     * @param  string  $business_connection_id  Unique identifier of the business connection on behalf of which the message to be edited was sent
     * @param  string  $inline_message_id  Required if chat_id and message_id are not specified. Identifier of the inline message.
     * @param  int|string  $live_period  New period in seconds during which the location can be updated, starting from the message send date. If 0x7FFFFFFF is specified, then the location can be updated forever. Otherwise, the new value must not exceed the current live_period by more than a day, and the live location expiration date must remain within the next 90 days. If not specified, then live_period remains unchanged.
     * @param  float|string  $horizontal_accuracy  The radius of uncertainty for the location, measured in meters; 0-1500
     * @param  int|string  $heading  Direction in which the user is moving, in degrees. Must be between 1 and 360 if specified.
     * @param  int|string  $proximity_alert_radius  The maximum distance for proximity alerts about approaching another chat member, in meters. Must be between 1 and 100000 if specified.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function editMessageLiveLocation($latitude, $longitude, $message_id = null, $reply_markup = null, $business_connection_id = null, $inline_message_id = null, $live_period = null, $horizontal_accuracy = null, $heading = null, $proximity_alert_radius = null): TelegramBroadcast
    {
        return $this->endpoint('editMessageLiveLocation', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to edit animation, audio, document, live photo, photo, or video messages, or to replace a text or a rich message with a media. If a message is part of a message album, then it can be edited only to an audio for audio albums, only to a document for document albums and to a photo, a live photo, or a video otherwise. When an inline message is edited, a new file can't be uploaded; use a previously uploaded file via its file_id or specify a URL. On success, if the edited message is not an inline message, the edited Message is returned, otherwise True is returned. Note that business messages that were not sent by the bot and do not contain an inline keyboard can only be edited within 48 hours from the time they were sent.
     *
     * @param  array  $media  A JSON-serialized object for the new media content of the message
     * @param  int|string  $message_id  Required if inline_message_id is not specified. Identifier of the message to edit.
     * @param  array  $reply_markup  A JSON-serialized object for a new inline keyboard
     * @param  string  $business_connection_id  Unique identifier of the business connection on behalf of which the message to be edited was sent
     * @param  string  $inline_message_id  Required if chat_id and message_id are not specified. Identifier of the inline message.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function editMessageMedia($media, $message_id = null, $reply_markup = null, $business_connection_id = null, $inline_message_id = null): TelegramBroadcast
    {
        return $this->endpoint('editMessageMedia', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to edit only the reply markup of messages. On success, if the edited message is not an inline message, the edited Message is returned, otherwise True is returned. Note that business messages that were not sent by the bot and do not contain an inline keyboard can only be edited within 48 hours from the time they were sent.
     *
     * @param  int|string  $message_id  Required if inline_message_id is not specified. Identifier of the message to edit.
     * @param  array  $reply_markup  A JSON-serialized object for an inline keyboard
     * @param  string  $business_connection_id  Unique identifier of the business connection on behalf of which the message to be edited was sent
     * @param  string  $inline_message_id  Required if chat_id and message_id are not specified. Identifier of the inline message.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function editMessageReplyMarkup($message_id = null, $reply_markup = null, $business_connection_id = null, $inline_message_id = null): TelegramBroadcast
    {
        return $this->endpoint('editMessageReplyMarkup', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to edit text, rich and game messages. On success, if the edited message is not an inline message, the edited Message is returned, otherwise True is returned. Note that business messages that were not sent by the bot and do not contain an inline keyboard can only be edited within 48 hours from the time they were sent.
     *
     * @param  string  $text  New text of the message, 1-4096 characters after entity parsing; required if rich_message isn't specified
     * @param  int|string  $message_id  Required if inline_message_id is not specified. Identifier of the message to edit.
     * @param  string  $parse_mode  Mode for parsing entities in the message text. See formatting options for more details.
     * @param  array  $reply_markup  A JSON-serialized object for an inline keyboard
     * @param  string  $business_connection_id  Unique identifier of the business connection on behalf of which the message to be edited was sent
     * @param  string  $inline_message_id  Required if chat_id and message_id are not specified. Identifier of the inline message.
     * @param  \LaraGram\Laraquest\Updates\MessageEntity[]|array|string  $entities  A JSON-serialized list of special entities that appear in message text, which can be specified instead of parse_mode
     * @param  array  $link_preview_options  Link preview generation options for the message
     * @param  array  $rich_message  New rich content of the message; required if text isn't specified. Direct upload of new files and explicit upload of files by a URL isn't supported when an inline message is edited.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function editMessageText($text = null, $message_id = null, $parse_mode = null, $reply_markup = null, $business_connection_id = null, $inline_message_id = null, $entities = null, $link_preview_options = null, $rich_message = null): TelegramBroadcast
    {
        return $this->endpoint('editMessageText', get_defined_vars(), 'chat_id');
    }

    /**
     * Allows the bot to cancel or re-enable extension of a subscription paid in Telegram Stars. Returns True on success.
     *
     * @param  string  $telegram_payment_charge_id  Telegram payment identifier for the subscription
     * @param  bool  $is_canceled  Pass True to cancel extension of the user subscription; the subscription must be active up to the end of the current subscription period. Pass False to allow the user to re-enable a subscription that was previously canceled by the bot.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function editUserStarSubscription($telegram_payment_charge_id, $is_canceled): TelegramBroadcast
    {
        return $this->endpoint('editUserStarSubscription', get_defined_vars(), 'user_id');
    }

    /**
     * Use this method to generate a new primary invite link for a chat; any previously generated primary link is revoked. The bot must be an administrator in the chat for this to work and must have the appropriate administrator rights. Returns the new invite link as String on success.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function exportChatInviteLink(): TelegramBroadcast
    {
        return $this->endpoint('exportChatInviteLink', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to forward messages of any kind. Service messages and messages with protected content can't be forwarded. On success, the sent Message is returned.
     *
     * @param  int|string  $from_chat_id  Unique identifier for the chat where the original message was sent (or username of the target bot, supergroup or channel in the format @username)
     * @param  int|string  $message_id  Message identifier in the chat specified in from_chat_id
     * @param  int|string  $message_thread_id  Unique identifier for the target message thread (topic) of a forum; for forum supergroups and private chats of bots with forum topic mode enabled only
     * @param  int|string  $direct_messages_topic_id  Identifier of the direct messages topic to which the message will be forwarded; required if the message is forwarded to a direct messages chat
     * @param  int|string  $video_start_timestamp  New start timestamp for the forwarded video in the message
     * @param  bool  $disable_notification  Sends the message silently. Users will receive a notification with no sound.
     * @param  bool  $protect_content  Protects the contents of the forwarded message from forwarding and saving
     * @param  string  $message_effect_id  Unique identifier of the message effect to be added to the message; only available when forwarding to private chats
     * @param  array  $suggested_post_parameters  A JSON-serialized object containing the parameters of the suggested post to send; for direct messages chats only
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function forwardMessage($from_chat_id, $message_id, $message_thread_id = null, $direct_messages_topic_id = null, $video_start_timestamp = null, $disable_notification = null, $protect_content = null, $message_effect_id = null, $suggested_post_parameters = null): TelegramBroadcast
    {
        return $this->endpoint('forwardMessage', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to forward multiple messages of any kind. If some of the specified messages can't be found or forwarded, they are skipped. Service messages and messages with protected content can't be forwarded. Album grouping is kept for forwarded messages. On success, an Array of MessageId of the sent messages is returned.
     *
     * @param  int|string  $from_chat_id  Unique identifier for the chat where the original messages were sent (or username of the target bot, supergroup or channel in the format @username)
     * @param  int[]  $message_ids  A JSON-serialized list of 1-100 identifiers of messages in the chat from_chat_id to forward. The identifiers must be specified in a strictly increasing order.
     * @param  int|string  $message_thread_id  Unique identifier for the target message thread (topic) of a forum; for forum supergroups and private chats of bots with forum topic mode enabled only
     * @param  int|string  $direct_messages_topic_id  Identifier of the direct messages topic to which the messages will be forwarded; required if the messages are forwarded to a direct messages chat
     * @param  bool  $disable_notification  Sends the messages silently. Users will receive a notification with no sound.
     * @param  bool  $protect_content  Protects the contents of the forwarded messages from forwarding and saving
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function forwardMessages($from_chat_id, $message_ids, $message_thread_id = null, $direct_messages_topic_id = null, $disable_notification = null, $protect_content = null): TelegramBroadcast
    {
        return $this->endpoint('forwardMessages', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to get up-to-date information about the chat. Returns a ChatFullInfo object on success.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function getChat(): TelegramBroadcast
    {
        return $this->endpoint('getChat', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to get a list of administrators in a chat. Returns an Array of ChatMember objects.
     *
     * @param  bool  $return_bots  Pass True to additionally receive all bots that are administrators of the chat. By default, bots other than the current bot are omitted.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function getChatAdministrators($return_bots = null): TelegramBroadcast
    {
        return $this->endpoint('getChatAdministrators', get_defined_vars(), 'chat_id');
    }

    /**
     * Returns the gifts owned by a chat. Returns OwnedGifts on success.
     *
     * @param  bool  $exclude_unsaved  Pass True to exclude gifts that aren't saved to the chat's profile page. Always True, unless the bot has the can_post_messages administrator right in the channel.
     * @param  bool  $exclude_saved  Pass True to exclude gifts that are saved to the chat's profile page. Always False, unless the bot has the can_post_messages administrator right in the channel.
     * @param  bool  $exclude_unlimited  Pass True to exclude gifts that can be purchased an unlimited number of times
     * @param  bool  $exclude_limited_upgradable  Pass True to exclude gifts that can be purchased a limited number of times and can be upgraded to unique
     * @param  bool  $exclude_limited_non_upgradable  Pass True to exclude gifts that can be purchased a limited number of times and can't be upgraded to unique
     * @param  bool  $exclude_from_blockchain  Pass True to exclude gifts that were assigned from the TON blockchain and can't be resold or transferred in Telegram
     * @param  bool  $exclude_unique  Pass True to exclude unique gifts
     * @param  bool  $sort_by_price  Pass True to sort results by gift price instead of send date. Sorting is applied before pagination.
     * @param  string  $offset  Offset of the first entry to return as received from the previous request; use an empty string to get the first chunk of results
     * @param  int|string  $limit  The maximum number of gifts to be returned; 1-100. Defaults to 100.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function getChatGifts($exclude_unsaved = null, $exclude_saved = null, $exclude_unlimited = null, $exclude_limited_upgradable = null, $exclude_limited_non_upgradable = null, $exclude_from_blockchain = null, $exclude_unique = null, $sort_by_price = null, $offset = null, $limit = null): TelegramBroadcast
    {
        return $this->endpoint('getChatGifts', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to get information about a member of a chat. The method is only guaranteed to work for other users if the bot is an administrator in the chat. Returns a ChatMember object on success.
     *
     * @param  int|string  $user_id  Unique identifier of the target user
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function getChatMember($user_id): TelegramBroadcast
    {
        return $this->endpoint('getChatMember', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to get the number of members in a chat. Returns Integer on success.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function getChatMemberCount(): TelegramBroadcast
    {
        return $this->endpoint('getChatMemberCount', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to get the current value of the bot's menu button in a private chat, or the default menu button. Returns MenuButton on success.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function getChatMenuButton(): TelegramBroadcast
    {
        return $this->endpoint('getChatMenuButton', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to get data for high score tables. Will return the score of the specified user and several of their neighbors in a game. Returns an Array of GameHighScore objects.
     *
     * @param  int|string  $user_id  Target user id
     * @param  int|string  $message_id  Required if inline_message_id is not specified. Identifier of the sent message.
     * @param  string  $inline_message_id  Required if chat_id and message_id are not specified. Identifier of the inline message.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function getGameHighScores($user_id, $message_id = null, $inline_message_id = null): TelegramBroadcast
    {
        return $this->endpoint('getGameHighScores', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to get the access settings of a managed bot. Returns a BotAccessSettings object on success.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function getManagedBotAccessSettings(): TelegramBroadcast
    {
        return $this->endpoint('getManagedBotAccessSettings', get_defined_vars(), 'user_id');
    }

    /**
     * Use this method to get the token of a managed bot. Returns the token as String on success.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function getManagedBotToken(): TelegramBroadcast
    {
        return $this->endpoint('getManagedBotToken', get_defined_vars(), 'user_id');
    }

    /**
     * Use this method to get the list of boosts added to a chat by a user. Requires administrator rights in the chat. Returns a UserChatBoosts object.
     *
     * @param  int|string  $user_id  Unique identifier of the target user
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function getUserChatBoosts($user_id): TelegramBroadcast
    {
        return $this->endpoint('getUserChatBoosts', get_defined_vars(), 'chat_id');
    }

    /**
     * Returns the gifts owned and hosted by a user. Returns OwnedGifts on success.
     *
     * @param  bool  $exclude_unlimited  Pass True to exclude gifts that can be purchased an unlimited number of times
     * @param  bool  $exclude_limited_upgradable  Pass True to exclude gifts that can be purchased a limited number of times and can be upgraded to unique
     * @param  bool  $exclude_limited_non_upgradable  Pass True to exclude gifts that can be purchased a limited number of times and can't be upgraded to unique
     * @param  bool  $exclude_from_blockchain  Pass True to exclude gifts that were assigned from the TON blockchain and can't be resold or transferred in Telegram
     * @param  bool  $exclude_unique  Pass True to exclude unique gifts
     * @param  bool  $sort_by_price  Pass True to sort results by gift price instead of send date. Sorting is applied before pagination.
     * @param  string  $offset  Offset of the first entry to return as received from the previous request; use an empty string to get the first chunk of results
     * @param  int|string  $limit  The maximum number of gifts to be returned; 1-100. Defaults to 100.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function getUserGifts($exclude_unlimited = null, $exclude_limited_upgradable = null, $exclude_limited_non_upgradable = null, $exclude_from_blockchain = null, $exclude_unique = null, $sort_by_price = null, $offset = null, $limit = null): TelegramBroadcast
    {
        return $this->endpoint('getUserGifts', get_defined_vars(), 'user_id');
    }

    /**
     * Use this method to get the last messages from the personal chat (i.e., the chat currently added to their profile) of a given user. On success, an Array of Message objects is returned.
     *
     * @param  int|string  $limit  The maximum number of messages to return; 1-20
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function getUserPersonalChatMessages($limit): TelegramBroadcast
    {
        return $this->endpoint('getUserPersonalChatMessages', get_defined_vars(), 'user_id');
    }

    /**
     * Use this method to get a list of profile audios for a user. Returns a UserProfileAudios object.
     *
     * @param  int|string  $offset  Sequential number of the first audio to be returned. By default, all audios are returned.
     * @param  int|string  $limit  Limits the number of audios to be retrieved. Values between 1-100 are accepted. Defaults to 100.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function getUserProfileAudios($offset = null, $limit = null): TelegramBroadcast
    {
        return $this->endpoint('getUserProfileAudios', get_defined_vars(), 'user_id');
    }

    /**
     * Use this method to get a list of profile pictures for a user. Returns a UserProfilePhotos object.
     *
     * @param  int|string  $offset  Sequential number of the first photo to be returned. By default, all photos are returned.
     * @param  int|string  $limit  Limits the number of photos to be retrieved. Values between 1-100 are accepted. Defaults to 100.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function getUserProfilePhotos($offset = null, $limit = null): TelegramBroadcast
    {
        return $this->endpoint('getUserProfilePhotos', get_defined_vars(), 'user_id');
    }

    /**
     * Gifts a Telegram Premium subscription to the given user. Returns True on success.
     *
     * @param  int|string  $month_count  Number of months the Telegram Premium subscription will be active for the user; must be one of 3, 6, or 12
     * @param  int|string  $star_count  Number of Telegram Stars to pay for the Telegram Premium subscription; must be 1000 for 3 months, 1500 for 6 months, and 2500 for 12 months
     * @param  string  $text  Text that will be shown along with the service message about the subscription; 0-128 characters
     * @param  string  $text_parse_mode  Mode for parsing entities in the text. See formatting options for more details. Entities other than “bold”, “italic”, “underline”, “strikethrough”, “spoiler”, “custom_emoji”, and “date_time” are ignored.
     * @param  \LaraGram\Laraquest\Updates\MessageEntity[]|array|string  $text_entities  A JSON-serialized list of special entities that appear in the gift text. It can be specified instead of text_parse_mode. Entities other than “bold”, “italic”, “underline”, “strikethrough”, “spoiler”, “custom_emoji”, and “date_time” are ignored.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function giftPremiumSubscription($month_count, $star_count, $text = null, $text_parse_mode = null, $text_entities = null): TelegramBroadcast
    {
        return $this->endpoint('giftPremiumSubscription', get_defined_vars(), 'user_id');
    }

    /**
     * Use this method to hide the 'General' topic in a forum supergroup chat. The bot must be an administrator in the chat for this to work and must have the can_manage_topics administrator rights. The topic will be automatically closed if it was open. Returns True on success.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function hideGeneralForumTopic(): TelegramBroadcast
    {
        return $this->endpoint('hideGeneralForumTopic', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method for your bot to leave a group, supergroup or channel. Returns True on success.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function leaveChat(): TelegramBroadcast
    {
        return $this->endpoint('leaveChat', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to add a message to the list of pinned messages in a chat. In private chats and channel direct messages chats, all non-service messages can be pinned. Conversely, the bot must be an administrator with the 'can_pin_messages' right or the 'can_edit_messages' right to pin messages in groups and channels respectively. Returns True on success.
     *
     * @param  int|string  $message_id  Identifier of a message to pin
     * @param  string  $business_connection_id  Unique identifier of the business connection on behalf of which the message will be pinned
     * @param  bool  $disable_notification  Pass True if it is not necessary to send a notification to all chat members about the new pinned message. Notifications are always disabled in channels and private chats.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function pinChatMessage($message_id, $business_connection_id = null, $disable_notification = null): TelegramBroadcast
    {
        return $this->endpoint('pinChatMessage', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to promote or demote a user in a supergroup or a channel. The bot must be an administrator in the chat for this to work and must have the appropriate administrator rights. Pass False for all boolean parameters to demote a user. Returns True on success.
     *
     * @param  int|string  $user_id  Unique identifier of the target user
     * @param  bool  $is_anonymous  Pass True if the administrator's presence in the chat is hidden
     * @param  bool  $can_manage_chat  Pass True if the administrator can access the chat event log, get boost list, see hidden supergroup and channel members, report spam messages, ignore slow mode, and send messages to the chat without paying Telegram Stars. Implied by any other administrator privilege.
     * @param  bool  $can_delete_messages  Pass True if the administrator can delete messages of other users
     * @param  bool  $can_manage_video_chats  Pass True if the administrator can manage video chats
     * @param  bool  $can_restrict_members  Pass True if the administrator can restrict, ban or unban chat members, or access supergroup statistics. For backward compatibility, defaults to True for promotions of channel administrators.
     * @param  bool  $can_promote_members  Pass True if the administrator can add new administrators with a subset of their own privileges or demote administrators that they have promoted, directly or indirectly (promoted by administrators that were appointed by him)
     * @param  bool  $can_change_info  Pass True if the administrator can change chat title, photo and other settings
     * @param  bool  $can_invite_users  Pass True if the administrator can invite new users to the chat
     * @param  bool  $can_post_stories  Pass True if the administrator can post stories to the chat
     * @param  bool  $can_edit_stories  Pass True if the administrator can edit stories posted by other users, post stories to the chat page, pin chat stories, and access the chat's story archive
     * @param  bool  $can_delete_stories  Pass True if the administrator can delete stories posted by other users
     * @param  bool  $can_post_messages  Pass True if the administrator can post messages in the channel, approve suggested posts, or access channel statistics; for channels only
     * @param  bool  $can_edit_messages  Pass True if the administrator can edit messages of other users and can pin messages; for channels only
     * @param  bool  $can_pin_messages  Pass True if the administrator can pin messages; for supergroups only
     * @param  bool  $can_manage_topics  Pass True if the user is allowed to create, rename, close, and reopen forum topics; for supergroups only
     * @param  bool  $can_manage_direct_messages  Pass True if the administrator can manage direct messages within the channel and decline suggested posts; for channels only
     * @param  bool  $can_manage_tags  Pass True if the administrator can edit the tags of regular members; for groups and supergroups only
     * @param  bool  $can_send_welcome_messages  Pass True if the administrator can manage chat welcome messages or directly send them in the case of bots
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function promoteChatMember($user_id, $is_anonymous = null, $can_manage_chat = null, $can_delete_messages = null, $can_manage_video_chats = null, $can_restrict_members = null, $can_promote_members = null, $can_change_info = null, $can_invite_users = null, $can_post_stories = null, $can_edit_stories = null, $can_delete_stories = null, $can_post_messages = null, $can_edit_messages = null, $can_pin_messages = null, $can_manage_topics = null, $can_manage_direct_messages = null, $can_manage_tags = null, $can_send_welcome_messages = null): TelegramBroadcast
    {
        return $this->endpoint('promoteChatMember', get_defined_vars(), 'chat_id');
    }

    /**
     * Marks incoming message as read on behalf of a business account. Requires the can_read_messages business bot right. Returns True on success.
     *
     * @param  string  $business_connection_id  Unique identifier of the business connection on behalf of which to read the message
     * @param  int|string  $message_id  Unique identifier of the message to mark as read
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function readBusinessMessage($business_connection_id, $message_id): TelegramBroadcast
    {
        return $this->endpoint('readBusinessMessage', get_defined_vars(), 'chat_id');
    }

    /**
     * Refunds a successful payment in Telegram Stars. Returns True on success.
     *
     * @param  string  $telegram_payment_charge_id  Telegram payment identifier
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function refundStarPayment($telegram_payment_charge_id): TelegramBroadcast
    {
        return $this->endpoint('refundStarPayment', get_defined_vars(), 'user_id');
    }

    /**
     * Removes verification from a chat that is currently verified on behalf of the organization represented by the bot. Returns True on success.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function removeChatVerification(): TelegramBroadcast
    {
        return $this->endpoint('removeChatVerification', get_defined_vars(), 'chat_id');
    }

    /**
     * Removes verification from a user who is currently verified on behalf of the organization represented by the bot. Returns True on success.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function removeUserVerification(): TelegramBroadcast
    {
        return $this->endpoint('removeUserVerification', get_defined_vars(), 'user_id');
    }

    /**
     * Use this method to reopen a closed topic in a forum supergroup chat. The bot must be an administrator in the chat for this to work and must have the can_manage_topics administrator rights, unless it is the creator of the topic. Returns True on success.
     *
     * @param  int|string  $message_thread_id  Unique identifier for the target message thread of the forum topic
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function reopenForumTopic($message_thread_id): TelegramBroadcast
    {
        return $this->endpoint('reopenForumTopic', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to reopen a closed 'General' topic in a forum supergroup chat. The bot must be an administrator in the chat for this to work and must have the can_manage_topics administrator rights. The topic will be automatically unhidden if it was hidden. Returns True on success.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function reopenGeneralForumTopic(): TelegramBroadcast
    {
        return $this->endpoint('reopenGeneralForumTopic', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to revoke the current token of a managed bot and generate a new one. Returns the new token as String on success.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function replaceManagedBotToken(): TelegramBroadcast
    {
        return $this->endpoint('replaceManagedBotToken', get_defined_vars(), 'user_id');
    }

    /**
     * Use this method to replace an existing sticker in a sticker set with a new one. The method is equivalent to calling deleteStickerFromSet, then addStickerToSet, then setStickerPositionInSet. Returns True on success.
     *
     * @param  array  $sticker  A JSON-serialized object with information about the added sticker. If exactly the same sticker had already been added to the set, then the set remains unchanged.
     * @param  string  $name  Sticker set name
     * @param  string  $old_sticker  File identifier of the replaced sticker
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function replaceStickerInSet($sticker, $name, $old_sticker): TelegramBroadcast
    {
        return $this->endpoint('replaceStickerInSet', get_defined_vars(), 'user_id');
    }

    /**
     * Use this method to restrict a user in a supergroup. The bot must be an administrator in the supergroup for this to work and must have the appropriate administrator rights. Pass True for all permissions to lift restrictions from a user. Returns True on success.
     *
     * @param  int|string  $user_id  Unique identifier of the target user
     * @param  array  $permissions  A JSON-serialized object for new user permissions
     * @param  bool  $use_independent_chat_permissions  Pass True if chat permissions are set independently. Otherwise, the can_send_other_messages and can_add_web_page_previews permissions will imply the can_send_messages, can_send_audios, can_send_documents, can_send_photos, can_send_videos, can_send_video_notes, and can_send_voice_notes permissions; the can_send_polls permission will imply the can_send_messages permission.
     * @param  int|string  $until_date  Date when restrictions will be lifted for the user; Unix time. If user is restricted for more than 366 days or less than 30 seconds from the current time, they are considered to be restricted forever.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function restrictChatMember($user_id, $permissions, $use_independent_chat_permissions = null, $until_date = null): TelegramBroadcast
    {
        return $this->endpoint('restrictChatMember', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to revoke an invite link created by the bot. If the primary link is revoked, a new link is automatically generated. The bot must be an administrator in the chat for this to work and must have the appropriate administrator rights. Returns the revoked invite link as ChatInviteLink object.
     *
     * @param  string  $invite_link  The invite link to revoke
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function revokeChatInviteLink($invite_link): TelegramBroadcast
    {
        return $this->endpoint('revokeChatInviteLink', get_defined_vars(), 'chat_id');
    }

    /**
     * Stores a message that can be sent by a user of a Mini App. Returns a PreparedInlineMessage object.
     *
     * @param  array  $result  A JSON-serialized object describing the message to be sent
     * @param  bool  $allow_user_chats  Pass True if the message can be sent to private chats with users
     * @param  bool  $allow_bot_chats  Pass True if the message can be sent to private chats with bots
     * @param  bool  $allow_group_chats  Pass True if the message can be sent to group and supergroup chats
     * @param  bool  $allow_channel_chats  Pass True if the message can be sent to channel chats
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function savePreparedInlineMessage($result, $allow_user_chats = null, $allow_bot_chats = null, $allow_group_chats = null, $allow_channel_chats = null): TelegramBroadcast
    {
        return $this->endpoint('savePreparedInlineMessage', get_defined_vars(), 'user_id');
    }

    /**
     * Stores a keyboard button that can be used by a user within a Mini App. Returns a PreparedKeyboardButton object.
     *
     * @param  array  $button  A JSON-serialized object describing the button to be saved. The button must be of the type request_users, request_chat, or request_managed_bot.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function savePreparedKeyboardButton($button): TelegramBroadcast
    {
        return $this->endpoint('savePreparedKeyboardButton', get_defined_vars(), 'user_id');
    }

    /**
     * Use this method to send animation files (GIF or H.264/MPEG-4 AVC video without sound). On success, the sent Message is returned. Bots can currently send animation files of up to 50 MB in size, this limit may be changed in the future.
     *
     * @param  array|string  $animation  Animation to send. Pass a file_id as String to send an animation that exists on the Telegram servers (recommended), pass an HTTP URL as a String for Telegram to get an animation from the Internet, or upload a new animation using multipart/form-data. More information on Sending Files »
     * @param  string  $caption  Animation caption (may also be used when resending animation by file_id), 0-1024 characters after entities parsing
     * @param  array|string  $thumbnail  Thumbnail of the file sent; can be ignored if thumbnail generation for the file is supported server-side. The thumbnail should be in JPEG format and less than 200 kB in size. A thumbnail's width and height should not exceed 320. Ignored if the file is not uploaded using multipart/form-data. Thumbnails can't be reused and can be only uploaded as a new file, so you can pass “attach://<file_attach_name>” if the thumbnail was uploaded using multipart/form-data under <file_attach_name>. More information on Sending Files »
     * @param  string  $parse_mode  Mode for parsing entities in the animation caption. See formatting options for more details.
     * @param  array  $reply_markup  Additional interface options. A JSON-serialized object for an inline keyboard, custom reply keyboard, instructions to remove a reply keyboard or to force a reply from the user.
     * @param  string  $business_connection_id  Unique identifier of the business connection on behalf of which the message will be sent
     * @param  int|string  $message_thread_id  Unique identifier for the target message thread (topic) of a forum; for forum supergroups and private chats of bots with forum topic mode enabled only
     * @param  int|string  $direct_messages_topic_id  Identifier of the direct messages topic to which the message will be sent; required if the message is sent to a direct messages chat
     * @param  array  $ephemeral_message_parameters  A JSON-serialized object containing the parameters of the ephemeral message to send
     * @param  int|string  $duration  Duration of sent animation in seconds
     * @param  int|string  $width  Animation width
     * @param  int|string  $height  Animation height
     * @param  \LaraGram\Laraquest\Updates\MessageEntity[]|array|string  $caption_entities  A JSON-serialized list of special entities that appear in the caption, which can be specified instead of parse_mode
     * @param  bool  $show_caption_above_media  Pass True if the caption must be shown above the message media
     * @param  bool  $has_spoiler  Pass True if the animation needs to be covered with a spoiler animation
     * @param  bool  $disable_notification  Sends the message silently. Users will receive a notification with no sound.
     * @param  bool  $protect_content  Protects the contents of the sent message from forwarding and saving
     * @param  bool  $allow_paid_broadcast  Pass True to allow up to 1000 messages per second, ignoring broadcasting limits for a fee of 0.1 Telegram Stars per message. The relevant Stars will be withdrawn from the bot's balance.
     * @param  string  $message_effect_id  Unique identifier of the message effect to be added to the message; for private chats only
     * @param  array  $suggested_post_parameters  A JSON-serialized object containing the parameters of the suggested post to send; for direct messages chats only. If the message is sent as a reply to another suggested post, then that suggested post is automatically declined.
     * @param  array  $reply_parameters  Description of the message to reply to
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function sendAnimation($animation, $caption = null, $thumbnail = null, $parse_mode = null, $reply_markup = null, $business_connection_id = null, $message_thread_id = null, $direct_messages_topic_id = null, $ephemeral_message_parameters = null, $duration = null, $width = null, $height = null, $caption_entities = null, $show_caption_above_media = null, $has_spoiler = null, $disable_notification = null, $protect_content = null, $allow_paid_broadcast = null, $message_effect_id = null, $suggested_post_parameters = null, $reply_parameters = null): TelegramBroadcast
    {
        return $this->endpoint('sendAnimation', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to send audio files, if you want Telegram clients to display them in the music player. Your audio must be in the .MP3 or .M4A format. On success, the sent Message is returned. Bots can currently send audio files of up to 50 MB in size, this limit may be changed in the future. For sending voice messages, use the sendVoice method instead.
     *
     * @param  array|string  $audio  Audio file to send. Pass a file_id as String to send an audio file that exists on the Telegram servers (recommended), pass an HTTP URL as a String for Telegram to get an audio file from the Internet, or upload a new one using multipart/form-data. More information on Sending Files »
     * @param  string  $caption  Audio caption, 0-1024 characters after entities parsing
     * @param  array|string  $thumbnail  Thumbnail of the file sent; can be ignored if thumbnail generation for the file is supported server-side. The thumbnail should be in JPEG format and less than 200 kB in size. A thumbnail's width and height should not exceed 320. Ignored if the file is not uploaded using multipart/form-data. Thumbnails can't be reused and can be only uploaded as a new file, so you can pass “attach://<file_attach_name>” if the thumbnail was uploaded using multipart/form-data under <file_attach_name>. More information on Sending Files »
     * @param  string  $parse_mode  Mode for parsing entities in the audio caption. See formatting options for more details.
     * @param  array  $reply_markup  Additional interface options. A JSON-serialized object for an inline keyboard, custom reply keyboard, instructions to remove a reply keyboard or to force a reply from the user.
     * @param  string  $business_connection_id  Unique identifier of the business connection on behalf of which the message will be sent
     * @param  int|string  $message_thread_id  Unique identifier for the target message thread (topic) of a forum; for forum supergroups and private chats of bots with forum topic mode enabled only
     * @param  int|string  $direct_messages_topic_id  Identifier of the direct messages topic to which the message will be sent; required if the message is sent to a direct messages chat
     * @param  array  $ephemeral_message_parameters  A JSON-serialized object containing the parameters of the ephemeral message to send
     * @param  \LaraGram\Laraquest\Updates\MessageEntity[]|array|string  $caption_entities  A JSON-serialized list of special entities that appear in the caption, which can be specified instead of parse_mode
     * @param  int|string  $duration  Duration of the audio in seconds
     * @param  string  $performer  Performer
     * @param  string  $title  Track name
     * @param  bool  $disable_notification  Sends the message silently. Users will receive a notification with no sound.
     * @param  bool  $protect_content  Protects the contents of the sent message from forwarding and saving
     * @param  bool  $allow_paid_broadcast  Pass True to allow up to 1000 messages per second, ignoring broadcasting limits for a fee of 0.1 Telegram Stars per message. The relevant Stars will be withdrawn from the bot's balance.
     * @param  string  $message_effect_id  Unique identifier of the message effect to be added to the message; for private chats only
     * @param  array  $suggested_post_parameters  A JSON-serialized object containing the parameters of the suggested post to send; for direct messages chats only. If the message is sent as a reply to another suggested post, then that suggested post is automatically declined.
     * @param  array  $reply_parameters  Description of the message to reply to
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function sendAudio($audio, $caption = null, $thumbnail = null, $parse_mode = null, $reply_markup = null, $business_connection_id = null, $message_thread_id = null, $direct_messages_topic_id = null, $ephemeral_message_parameters = null, $caption_entities = null, $duration = null, $performer = null, $title = null, $disable_notification = null, $protect_content = null, $allow_paid_broadcast = null, $message_effect_id = null, $suggested_post_parameters = null, $reply_parameters = null): TelegramBroadcast
    {
        return $this->endpoint('sendAudio', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method when you need to tell the user that something is happening on the bot's side. The status is set for 5 seconds or less (when a message arrives from your bot, Telegram clients clear its typing status). Returns True on success. We only recommend using this method when a response from the bot will take a noticeable amount of time to arrive.
     *
     * @param  string  $action  Type of action to broadcast. Choose one, depending on what the user is about to receive: typing for text messages, upload_photo for photos, record_video or upload_video for videos, record_voice or upload_voice for voice notes, upload_document for general files, choose_sticker for stickers, find_location for location data, record_video_note or upload_video_note for video notes.
     * @param  string  $business_connection_id  Unique identifier of the business connection on behalf of which the action will be sent
     * @param  int|string  $message_thread_id  Unique identifier for the target message thread or topic of a forum; for supergroups and private chats of bots with forum topic mode enabled only
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function sendChatAction($action, $business_connection_id = null, $message_thread_id = null): TelegramBroadcast
    {
        return $this->endpoint('sendChatAction', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to send a checklist on behalf of a connected business account. On success, the sent Message is returned.
     *
     * @param  string  $business_connection_id  Unique identifier of the business connection on behalf of which the message will be sent
     * @param  array  $checklist  A JSON-serialized object for the checklist to send
     * @param  array  $reply_markup  A JSON-serialized object for an inline keyboard
     * @param  bool  $disable_notification  Sends the message silently. Users will receive a notification with no sound.
     * @param  bool  $protect_content  Protects the contents of the sent message from forwarding and saving
     * @param  string  $message_effect_id  Unique identifier of the message effect to be added to the message
     * @param  array  $reply_parameters  A JSON-serialized object for description of the message to reply to
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function sendChecklist($business_connection_id, $checklist, $reply_markup = null, $disable_notification = null, $protect_content = null, $message_effect_id = null, $reply_parameters = null): TelegramBroadcast
    {
        return $this->endpoint('sendChecklist', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to send phone contacts. On success, the sent Message is returned.
     *
     * @param  string  $phone_number  Contact's phone number
     * @param  string  $first_name  Contact's first name
     * @param  array  $reply_markup  Additional interface options. A JSON-serialized object for an inline keyboard, custom reply keyboard, instructions to remove a reply keyboard or to force a reply from the user.
     * @param  string  $business_connection_id  Unique identifier of the business connection on behalf of which the message will be sent
     * @param  int|string  $message_thread_id  Unique identifier for the target message thread (topic) of a forum; for forum supergroups and private chats of bots with forum topic mode enabled only
     * @param  int|string  $direct_messages_topic_id  Identifier of the direct messages topic to which the message will be sent; required if the message is sent to a direct messages chat
     * @param  array  $ephemeral_message_parameters  A JSON-serialized object containing the parameters of the ephemeral message to send
     * @param  string  $last_name  Contact's last name
     * @param  string  $vcard  Additional data about the contact in the form of a vCard, 0-2048 bytes
     * @param  bool  $disable_notification  Sends the message silently. Users will receive a notification with no sound.
     * @param  bool  $protect_content  Protects the contents of the sent message from forwarding and saving
     * @param  bool  $allow_paid_broadcast  Pass True to allow up to 1000 messages per second, ignoring broadcasting limits for a fee of 0.1 Telegram Stars per message. The relevant Stars will be withdrawn from the bot's balance.
     * @param  string  $message_effect_id  Unique identifier of the message effect to be added to the message; for private chats only
     * @param  array  $suggested_post_parameters  A JSON-serialized object containing the parameters of the suggested post to send; for direct messages chats only. If the message is sent as a reply to another suggested post, then that suggested post is automatically declined.
     * @param  array  $reply_parameters  Description of the message to reply to
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function sendContact($phone_number, $first_name, $reply_markup = null, $business_connection_id = null, $message_thread_id = null, $direct_messages_topic_id = null, $ephemeral_message_parameters = null, $last_name = null, $vcard = null, $disable_notification = null, $protect_content = null, $allow_paid_broadcast = null, $message_effect_id = null, $suggested_post_parameters = null, $reply_parameters = null): TelegramBroadcast
    {
        return $this->endpoint('sendContact', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to send an animated emoji that will display a random value. On success, the sent Message is returned.
     *
     * @param  array  $reply_markup  Additional interface options. A JSON-serialized object for an inline keyboard, custom reply keyboard, instructions to remove a reply keyboard or to force a reply from the user.
     * @param  string  $business_connection_id  Unique identifier of the business connection on behalf of which the message will be sent
     * @param  int|string  $message_thread_id  Unique identifier for the target message thread (topic) of a forum; for forum supergroups and private chats of bots with forum topic mode enabled only
     * @param  int|string  $direct_messages_topic_id  Identifier of the direct messages topic to which the message will be sent; required if the message is sent to a direct messages chat
     * @param  string  $emoji  Emoji on which the dice throw animation is based. Currently, must be one of “”, “”, “”, “”, “”, or “”. Dice can have values 1-6 for “”, “” and “”, values 1-5 for “” and “”, and values 1-64 for “”. Defaults to “”.
     * @param  bool  $disable_notification  Sends the message silently. Users will receive a notification with no sound.
     * @param  bool  $protect_content  Protects the contents of the sent message from forwarding
     * @param  bool  $allow_paid_broadcast  Pass True to allow up to 1000 messages per second, ignoring broadcasting limits for a fee of 0.1 Telegram Stars per message. The relevant Stars will be withdrawn from the bot's balance.
     * @param  string  $message_effect_id  Unique identifier of the message effect to be added to the message; for private chats only
     * @param  array  $suggested_post_parameters  A JSON-serialized object containing the parameters of the suggested post to send; for direct messages chats only. If the message is sent as a reply to another suggested post, then that suggested post is automatically declined.
     * @param  array  $reply_parameters  Description of the message to reply to
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function sendDice($reply_markup = null, $business_connection_id = null, $message_thread_id = null, $direct_messages_topic_id = null, $emoji = null, $disable_notification = null, $protect_content = null, $allow_paid_broadcast = null, $message_effect_id = null, $suggested_post_parameters = null, $reply_parameters = null): TelegramBroadcast
    {
        return $this->endpoint('sendDice', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to send general files. On success, the sent Message is returned. Bots can currently send files of any type of up to 50 MB in size, this limit may be changed in the future.
     *
     * @param  array|string  $document  File to send. Pass a file_id as String to send a file that exists on the Telegram servers (recommended), pass an HTTP URL as a String for Telegram to get a file from the Internet, or upload a new one using multipart/form-data. More information on Sending Files »
     * @param  string  $caption  Document caption (may also be used when resending documents by file_id), 0-1024 characters after entities parsing
     * @param  array|string  $thumbnail  Thumbnail of the file sent; can be ignored if thumbnail generation for the file is supported server-side. The thumbnail should be in JPEG format and less than 200 kB in size. A thumbnail's width and height should not exceed 320. Ignored if the file is not uploaded using multipart/form-data. Thumbnails can't be reused and can be only uploaded as a new file, so you can pass “attach://<file_attach_name>” if the thumbnail was uploaded using multipart/form-data under <file_attach_name>. More information on Sending Files »
     * @param  string  $parse_mode  Mode for parsing entities in the document caption. See formatting options for more details.
     * @param  array  $reply_markup  Additional interface options. A JSON-serialized object for an inline keyboard, custom reply keyboard, instructions to remove a reply keyboard or to force a reply from the user.
     * @param  string  $business_connection_id  Unique identifier of the business connection on behalf of which the message will be sent
     * @param  int|string  $message_thread_id  Unique identifier for the target message thread (topic) of a forum; for forum supergroups and private chats of bots with forum topic mode enabled only
     * @param  int|string  $direct_messages_topic_id  Identifier of the direct messages topic to which the message will be sent; required if the message is sent to a direct messages chat
     * @param  array  $ephemeral_message_parameters  A JSON-serialized object containing the parameters of the ephemeral message to send
     * @param  \LaraGram\Laraquest\Updates\MessageEntity[]|array|string  $caption_entities  A JSON-serialized list of special entities that appear in the caption, which can be specified instead of parse_mode
     * @param  bool  $disable_content_type_detection  Disables automatic server-side content type detection for files uploaded using multipart/form-data
     * @param  bool  $disable_notification  Sends the message silently. Users will receive a notification with no sound.
     * @param  bool  $protect_content  Protects the contents of the sent message from forwarding and saving
     * @param  bool  $allow_paid_broadcast  Pass True to allow up to 1000 messages per second, ignoring broadcasting limits for a fee of 0.1 Telegram Stars per message. The relevant Stars will be withdrawn from the bot's balance.
     * @param  string  $message_effect_id  Unique identifier of the message effect to be added to the message; for private chats only
     * @param  array  $suggested_post_parameters  A JSON-serialized object containing the parameters of the suggested post to send; for direct messages chats only. If the message is sent as a reply to another suggested post, then that suggested post is automatically declined.
     * @param  array  $reply_parameters  Description of the message to reply to
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function sendDocument($document, $caption = null, $thumbnail = null, $parse_mode = null, $reply_markup = null, $business_connection_id = null, $message_thread_id = null, $direct_messages_topic_id = null, $ephemeral_message_parameters = null, $caption_entities = null, $disable_content_type_detection = null, $disable_notification = null, $protect_content = null, $allow_paid_broadcast = null, $message_effect_id = null, $suggested_post_parameters = null, $reply_parameters = null): TelegramBroadcast
    {
        return $this->endpoint('sendDocument', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to send a game. On success, the sent Message is returned.
     *
     * @param  string  $game_short_name  Short name of the game, serves as the unique identifier for the game. Set up your games via @BotFather.
     * @param  array  $reply_markup  A JSON-serialized object for an inline keyboard. If empty, one 'Play game_title' button will be shown. If not empty, the first button must launch the game.
     * @param  string  $business_connection_id  Unique identifier of the business connection on behalf of which the message will be sent
     * @param  int|string  $message_thread_id  Unique identifier for the target message thread (topic) of a forum; for forum supergroups and private chats of bots with forum topic mode enabled only
     * @param  bool  $disable_notification  Sends the message silently. Users will receive a notification with no sound.
     * @param  bool  $protect_content  Protects the contents of the sent message from forwarding and saving
     * @param  bool  $allow_paid_broadcast  Pass True to allow up to 1000 messages per second, ignoring broadcasting limits for a fee of 0.1 Telegram Stars per message. The relevant Stars will be withdrawn from the bot's balance.
     * @param  string  $message_effect_id  Unique identifier of the message effect to be added to the message; for private chats only
     * @param  array  $reply_parameters  Description of the message to reply to
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function sendGame($game_short_name, $reply_markup = null, $business_connection_id = null, $message_thread_id = null, $disable_notification = null, $protect_content = null, $allow_paid_broadcast = null, $message_effect_id = null, $reply_parameters = null): TelegramBroadcast
    {
        return $this->endpoint('sendGame', get_defined_vars(), 'chat_id');
    }

    /**
     * Sends a gift to the given user or channel chat. The gift can't be converted to Telegram Stars by the receiver. Returns True on success.
     *
     * @param  string  $gift_id  Identifier of the gift; limited gifts can't be sent to channel chats
     * @param  string  $text  Text that will be shown along with the gift; 0-128 characters
     * @param  int|string  $user_id  Required if chat_id is not specified. Unique identifier of the target user who will receive the gift.
     * @param  bool  $pay_for_upgrade  Pass True to pay for the gift upgrade from the bot's balance, thereby making the upgrade free for the receiver
     * @param  string  $text_parse_mode  Mode for parsing entities in the text. See formatting options for more details. Entities other than “bold”, “italic”, “underline”, “strikethrough”, “spoiler”, “custom_emoji”, and “date_time” are ignored.
     * @param  \LaraGram\Laraquest\Updates\MessageEntity[]|array|string  $text_entities  A JSON-serialized list of special entities that appear in the gift text. It can be specified instead of text_parse_mode. Entities other than “bold”, “italic”, “underline”, “strikethrough”, “spoiler”, “custom_emoji”, and “date_time” are ignored.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function sendGift($gift_id, $text = null, $user_id = null, $pay_for_upgrade = null, $text_parse_mode = null, $text_entities = null): TelegramBroadcast
    {
        return $this->endpoint('sendGift', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to send invoices. On success, the sent Message is returned.
     *
     * @param  string  $title  Product name, 1-32 characters
     * @param  string  $description  Product description, 1-255 characters
     * @param  string  $payload  Bot-defined invoice payload, 1-128 bytes. This will not be displayed to the user, use it for your internal processes.
     * @param  string  $currency  Three-letter ISO 4217 currency code, see more on currencies. Pass “XTR” for payments in Telegram Stars.
     * @param  \LaraGram\Laraquest\Updates\LabeledPrice[]|array|string  $prices  Price breakdown, a JSON-serialized list of components (e.g. product price, tax, discount, delivery cost, delivery tax, bonus, etc.). Must contain exactly one item for payments in Telegram Stars.
     * @param  array  $reply_markup  A JSON-serialized object for an inline keyboard. If empty, one 'Pay total price' button will be shown. If not empty, the first button must be a Pay button.
     * @param  int|string  $message_thread_id  Unique identifier for the target message thread (topic) of a forum; for forum supergroups and private chats of bots with forum topic mode enabled only
     * @param  int|string  $direct_messages_topic_id  Identifier of the direct messages topic to which the message will be sent; required if the message is sent to a direct messages chat
     * @param  string  $provider_token  Payment provider token, obtained via @BotFather. Pass an empty string for payments in Telegram Stars.
     * @param  int|string  $max_tip_amount  The maximum accepted amount for tips in the smallest units of the currency (integer, not float/double). For example, for a maximum tip of US$ 1.45 pass max_tip_amount = 145. See the exp parameter in currencies.json, it shows the number of digits past the decimal point for each currency (2 for the majority of currencies). Defaults to 0. Not supported for payments in Telegram Stars.
     * @param  int[]  $suggested_tip_amounts  A JSON-serialized Array of suggested amounts of tips in the smallest units of the currency (integer, not float/double). At most 4 suggested tip amounts can be specified. The suggested tip amounts must be positive, passed in a strictly increased order and must not exceed max_tip_amount.
     * @param  string  $start_parameter  Unique deep-linking parameter. If left empty, forwarded copies of the sent message will have a Pay button, allowing multiple users to pay directly from the forwarded message, using the same invoice. If non-empty, forwarded copies of the sent message will have a URL button with a deep link to the bot (instead of a Pay button), with the value used as the start parameter.
     * @param  string  $provider_data  JSON-serialized data about the invoice, which will be shared with the payment provider. A detailed description of required fields should be provided by the payment provider.
     * @param  string  $photo_url  URL of the product photo for the invoice. Can be a photo of the goods or a marketing image for a service. People like it better when they see what they are paying for.
     * @param  int|string  $photo_size  Photo size in bytes
     * @param  int|string  $photo_width  Photo width
     * @param  int|string  $photo_height  Photo height
     * @param  bool  $need_name  Pass True if you require the user's full name to complete the order. Ignored for payments in Telegram Stars.
     * @param  bool  $need_phone_number  Pass True if you require the user's phone number to complete the order. Ignored for payments in Telegram Stars.
     * @param  bool  $need_email  Pass True if you require the user's email address to complete the order. Ignored for payments in Telegram Stars.
     * @param  bool  $need_shipping_address  Pass True if you require the user's shipping address to complete the order. Ignored for payments in Telegram Stars.
     * @param  bool  $send_phone_number_to_provider  Pass True if the user's phone number should be sent to the provider. Ignored for payments in Telegram Stars.
     * @param  bool  $send_email_to_provider  Pass True if the user's email address should be sent to the provider. Ignored for payments in Telegram Stars.
     * @param  bool  $is_flexible  Pass True if the final price depends on the shipping method. Ignored for payments in Telegram Stars.
     * @param  bool  $disable_notification  Sends the message silently. Users will receive a notification with no sound.
     * @param  bool  $protect_content  Protects the contents of the sent message from forwarding and saving
     * @param  bool  $allow_paid_broadcast  Pass True to allow up to 1000 messages per second, ignoring broadcasting limits for a fee of 0.1 Telegram Stars per message. The relevant Stars will be withdrawn from the bot's balance.
     * @param  string  $message_effect_id  Unique identifier of the message effect to be added to the message; for private chats only
     * @param  array  $suggested_post_parameters  A JSON-serialized object containing the parameters of the suggested post to send; for direct messages chats only. If the message is sent as a reply to another suggested post, then that suggested post is automatically declined.
     * @param  array  $reply_parameters  Description of the message to reply to
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function sendInvoice($title, $description, $payload, $currency, $prices, $reply_markup = null, $message_thread_id = null, $direct_messages_topic_id = null, $provider_token = null, $max_tip_amount = null, $suggested_tip_amounts = null, $start_parameter = null, $provider_data = null, $photo_url = null, $photo_size = null, $photo_width = null, $photo_height = null, $need_name = null, $need_phone_number = null, $need_email = null, $need_shipping_address = null, $send_phone_number_to_provider = null, $send_email_to_provider = null, $is_flexible = null, $disable_notification = null, $protect_content = null, $allow_paid_broadcast = null, $message_effect_id = null, $suggested_post_parameters = null, $reply_parameters = null): TelegramBroadcast
    {
        return $this->endpoint('sendInvoice', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to send live photos. On success, the sent Message is returned.
     *
     * @param  array|string  $photo  The static photo to send. Pass a file_id as String to send a photo that exists on the Telegram servers (recommended) or upload a new video using multipart/form-data. More information on Sending Files ». Sending live photos by a URL is currently unsupported.
     * @param  array|string  $live_photo  Live photo video to send. The video must be no longer than 10 seconds and must not exceed 10 MB in size. Pass a file_id as String to send a video that exists on the Telegram servers (recommended) or upload a new video using multipart/form-data. More information on Sending Files ». Sending live photos by a URL is currently unsupported.
     * @param  string  $caption  Video caption (may also be used when resending videos by file_id), 0-1024 characters after entities parsing
     * @param  string  $parse_mode  Mode for parsing entities in the video caption. See formatting options for more details.
     * @param  array  $reply_markup  Additional interface options. A JSON-serialized object for an inline keyboard, custom reply keyboard, instructions to remove a reply keyboard or to force a reply from the user.
     * @param  string  $business_connection_id  Unique identifier of the business connection on behalf of which the message will be sent
     * @param  int|string  $message_thread_id  Unique identifier for the target message thread (topic) of a forum; for forum supergroups and private chats of bots with forum topic mode enabled only
     * @param  int|string  $direct_messages_topic_id  Identifier of the direct messages topic to which the message will be sent; required if the message is sent to a direct messages chat
     * @param  array  $ephemeral_message_parameters  A JSON-serialized object containing the parameters of the ephemeral message to send
     * @param  \LaraGram\Laraquest\Updates\MessageEntity[]|array|string  $caption_entities  A JSON-serialized list of special entities that appear in the caption, which can be specified instead of parse_mode
     * @param  bool  $show_caption_above_media  Pass True if the caption must be shown above the message media
     * @param  bool  $has_spoiler  Pass True if the video needs to be covered with a spoiler animation
     * @param  bool  $disable_notification  Sends the message silently. Users will receive a notification with no sound.
     * @param  bool  $protect_content  Protects the contents of the sent message from forwarding and saving
     * @param  bool  $allow_paid_broadcast  Pass True to allow up to 1000 messages per second, ignoring broadcasting limits for a fee of 0.1 Telegram Stars per message. The relevant Stars will be withdrawn from the bot's balance.
     * @param  string  $message_effect_id  Unique identifier of the message effect to be added to the message; for private chats only
     * @param  array  $suggested_post_parameters  A JSON-serialized object containing the parameters of the suggested post to send; for direct messages chats only. If the message is sent as a reply to another suggested post, then that suggested post is automatically declined.
     * @param  array  $reply_parameters  Description of the message to reply to
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function sendLivePhoto($photo, $live_photo, $caption = null, $parse_mode = null, $reply_markup = null, $business_connection_id = null, $message_thread_id = null, $direct_messages_topic_id = null, $ephemeral_message_parameters = null, $caption_entities = null, $show_caption_above_media = null, $has_spoiler = null, $disable_notification = null, $protect_content = null, $allow_paid_broadcast = null, $message_effect_id = null, $suggested_post_parameters = null, $reply_parameters = null): TelegramBroadcast
    {
        return $this->endpoint('sendLivePhoto', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to send point on the map. On success, the sent Message is returned.
     *
     * @param  float|string  $latitude  Latitude of the location
     * @param  float|string  $longitude  Longitude of the location
     * @param  array  $reply_markup  Additional interface options. A JSON-serialized object for an inline keyboard, custom reply keyboard, instructions to remove a reply keyboard or to force a reply from the user.
     * @param  string  $business_connection_id  Unique identifier of the business connection on behalf of which the message will be sent
     * @param  int|string  $message_thread_id  Unique identifier for the target message thread (topic) of a forum; for forum supergroups and private chats of bots with forum topic mode enabled only
     * @param  int|string  $direct_messages_topic_id  Identifier of the direct messages topic to which the message will be sent; required if the message is sent to a direct messages chat
     * @param  array  $ephemeral_message_parameters  A JSON-serialized object containing the parameters of the ephemeral message to send
     * @param  float|string  $horizontal_accuracy  The radius of uncertainty for the location, measured in meters; 0-1500
     * @param  int|string  $live_period  Period in seconds during which the location will be updated (see Live Locations), must be between 60 and 86400, or 0x7FFFFFFF for live locations that can be edited indefinitely. Must be 0 for ephemeral messages.
     * @param  int|string  $heading  For live locations, a direction in which the user is moving, in degrees. Must be between 1 and 360 if specified.
     * @param  int|string  $proximity_alert_radius  For live locations, a maximum distance for proximity alerts about approaching another chat member, in meters. Must be between 1 and 100000 if specified.
     * @param  bool  $disable_notification  Sends the message silently. Users will receive a notification with no sound.
     * @param  bool  $protect_content  Protects the contents of the sent message from forwarding and saving
     * @param  bool  $allow_paid_broadcast  Pass True to allow up to 1000 messages per second, ignoring broadcasting limits for a fee of 0.1 Telegram Stars per message. The relevant Stars will be withdrawn from the bot's balance.
     * @param  string  $message_effect_id  Unique identifier of the message effect to be added to the message; for private chats only
     * @param  array  $suggested_post_parameters  A JSON-serialized object containing the parameters of the suggested post to send; for direct messages chats only. If the message is sent as a reply to another suggested post, then that suggested post is automatically declined.
     * @param  array  $reply_parameters  Description of the message to reply to
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function sendLocation($latitude, $longitude, $reply_markup = null, $business_connection_id = null, $message_thread_id = null, $direct_messages_topic_id = null, $ephemeral_message_parameters = null, $horizontal_accuracy = null, $live_period = null, $heading = null, $proximity_alert_radius = null, $disable_notification = null, $protect_content = null, $allow_paid_broadcast = null, $message_effect_id = null, $suggested_post_parameters = null, $reply_parameters = null): TelegramBroadcast
    {
        return $this->endpoint('sendLocation', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to send a group of photos, live photos, videos, documents or audios as an album. Documents and audio files can be only grouped in an album with messages of the same type. On success, an Array of Message objects that were sent is returned.
     *
     * @param  \LaraGram\Laraquest\Updates\InputMediaAudio, InputMediaDocument, InputMediaLivePhoto, InputMediaPhoto and InputMediaVideo[]|array|string  $media  A JSON-serialized Array describing messages to be sent, must include 2-10 items
     * @param  string  $business_connection_id  Unique identifier of the business connection on behalf of which the message will be sent
     * @param  int|string  $message_thread_id  Unique identifier for the target message thread (topic) of a forum; for forum supergroups and private chats of bots with forum topic mode enabled only
     * @param  int|string  $direct_messages_topic_id  Identifier of the direct messages topic to which the messages will be sent; required if the messages are sent to a direct messages chat
     * @param  bool  $disable_notification  Sends messages silently. Users will receive a notification with no sound.
     * @param  bool  $protect_content  Protects the contents of the sent messages from forwarding and saving
     * @param  bool  $allow_paid_broadcast  Pass True to allow up to 1000 messages per second, ignoring broadcasting limits for a fee of 0.1 Telegram Stars per message. The relevant Stars will be withdrawn from the bot's balance.
     * @param  string  $message_effect_id  Unique identifier of the message effect to be added to the message; for private chats only
     * @param  array  $reply_parameters  Description of the message to reply to
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function sendMediaGroup($media, $business_connection_id = null, $message_thread_id = null, $direct_messages_topic_id = null, $disable_notification = null, $protect_content = null, $allow_paid_broadcast = null, $message_effect_id = null, $reply_parameters = null): TelegramBroadcast
    {
        return $this->endpoint('sendMediaGroup', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to send text messages. On success, the sent Message is returned.
     *
     * @param  string  $text  Text of the message to be sent, 1-4096 characters after entities parsing
     * @param  string  $parse_mode  Mode for parsing entities in the message text. See formatting options for more details.
     * @param  array  $reply_markup  Additional interface options. A JSON-serialized object for an inline keyboard, custom reply keyboard, instructions to remove a reply keyboard or to force a reply from the user.
     * @param  string  $business_connection_id  Unique identifier of the business connection on behalf of which the message will be sent
     * @param  int|string  $message_thread_id  Unique identifier for the target message thread (topic) of a forum; for forum supergroups and private chats of bots with forum topic mode enabled only
     * @param  int|string  $direct_messages_topic_id  Identifier of the direct messages topic to which the message will be sent; required if the message is sent to a direct messages chat
     * @param  array  $ephemeral_message_parameters  A JSON-serialized object containing the parameters of the ephemeral message to send
     * @param  \LaraGram\Laraquest\Updates\MessageEntity[]|array|string  $entities  A JSON-serialized list of special entities that appear in message text, which can be specified instead of parse_mode
     * @param  array  $link_preview_options  Link preview generation options for the message
     * @param  bool  $disable_notification  Sends the message silently. Users will receive a notification with no sound.
     * @param  bool  $protect_content  Protects the contents of the sent message from forwarding and saving
     * @param  bool  $allow_paid_broadcast  Pass True to allow up to 1000 messages per second, ignoring broadcasting limits for a fee of 0.1 Telegram Stars per message. The relevant Stars will be withdrawn from the bot's balance.
     * @param  string  $message_effect_id  Unique identifier of the message effect to be added to the message; for private chats only
     * @param  array  $suggested_post_parameters  A JSON-serialized object containing the parameters of the suggested post to send; for direct messages chats only. If the message is sent as a reply to another suggested post, then that suggested post is automatically declined.
     * @param  array  $reply_parameters  Description of the message to reply to
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function sendMessage($text, $parse_mode = null, $reply_markup = null, $business_connection_id = null, $message_thread_id = null, $direct_messages_topic_id = null, $ephemeral_message_parameters = null, $entities = null, $link_preview_options = null, $disable_notification = null, $protect_content = null, $allow_paid_broadcast = null, $message_effect_id = null, $suggested_post_parameters = null, $reply_parameters = null): TelegramBroadcast
    {
        return $this->endpoint('sendMessage', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to stream a partial message to a user while the message is being generated. Note that the streamed draft is ephemeral and acts as a temporary 30-second preview - once the output is finalized, you must call sendMessage with the complete message to persist it in the user's chat. Returns True on success.
     *
     * @param  int|string  $draft_id  Unique identifier of the message draft; must be non-zero. Changes to drafts with the same identifier are animated. Otherwise, the draft is replaced without animation.
     * @param  string  $text  Text of the message to be sent, 0-4096 characters after entities parsing. Pass an empty text to show a “Thinking…” placeholder.
     * @param  string  $parse_mode  Mode for parsing entities in the message text. See formatting options for more details.
     * @param  int|string  $message_thread_id  Unique identifier for the target message thread
     * @param  \LaraGram\Laraquest\Updates\MessageEntity[]|array|string  $entities  A JSON-serialized list of special entities that appear in message text, which can be specified instead of parse_mode
     * @param  bool  $can_stop  Pass True to show the user a button to stop further drafts. The bot will receive an Update “stopped_message_generation” if the user presses the button.
     * @param  bool  $keep_on_stop  Pass True to keep the draft in the chat when the button is pressed. The draft will still disappear after a short time or if the bot sends a message. To fully preserve the partial draft, the bot should send it as a new message.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function sendMessageDraft($draft_id, $text = null, $parse_mode = null, $message_thread_id = null, $entities = null, $can_stop = null, $keep_on_stop = null): TelegramBroadcast
    {
        return $this->endpoint('sendMessageDraft', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to send paid media. On success, the sent Message is returned.
     *
     * @param  \LaraGram\Laraquest\Updates\InputPaidMedia[]|array|string  $media  A JSON-serialized Array describing the media to be sent; up to 10 items
     * @param  int|string  $star_count  The number of Telegram Stars that must be paid to buy access to the media; 1-25000
     * @param  string  $caption  Media caption, 0-1024 characters after entities parsing
     * @param  string  $parse_mode  Mode for parsing entities in the media caption. See formatting options for more details.
     * @param  array  $reply_markup  Additional interface options. A JSON-serialized object for an inline keyboard, custom reply keyboard, instructions to remove a reply keyboard or to force a reply from the user.
     * @param  string  $business_connection_id  Unique identifier of the business connection on behalf of which the message will be sent
     * @param  int|string  $message_thread_id  Unique identifier for the target message thread (topic) of a forum; for forum supergroups and private chats of bots with forum topic mode enabled only
     * @param  int|string  $direct_messages_topic_id  Identifier of the direct messages topic to which the message will be sent; required if the message is sent to a direct messages chat
     * @param  string  $payload  Bot-defined paid media payload, 0-128 bytes. This will not be displayed to the user, use it for your internal processes.
     * @param  \LaraGram\Laraquest\Updates\MessageEntity[]|array|string  $caption_entities  A JSON-serialized list of special entities that appear in the caption, which can be specified instead of parse_mode
     * @param  bool  $show_caption_above_media  Pass True if the caption must be shown above the message media
     * @param  bool  $disable_notification  Sends the message silently. Users will receive a notification with no sound.
     * @param  bool  $protect_content  Protects the contents of the sent message from forwarding and saving
     * @param  bool  $allow_paid_broadcast  Pass True to allow up to 1000 messages per second, ignoring broadcasting limits for a fee of 0.1 Telegram Stars per message. The relevant Stars will be withdrawn from the bot's balance.
     * @param  array  $suggested_post_parameters  A JSON-serialized object containing the parameters of the suggested post to send; for direct messages chats only. If the message is sent as a reply to another suggested post, then that suggested post is automatically declined.
     * @param  array  $reply_parameters  Description of the message to reply to
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function sendPaidMedia($media, $star_count, $caption = null, $parse_mode = null, $reply_markup = null, $business_connection_id = null, $message_thread_id = null, $direct_messages_topic_id = null, $payload = null, $caption_entities = null, $show_caption_above_media = null, $disable_notification = null, $protect_content = null, $allow_paid_broadcast = null, $suggested_post_parameters = null, $reply_parameters = null): TelegramBroadcast
    {
        return $this->endpoint('sendPaidMedia', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to send photos. On success, the sent Message is returned.
     *
     * @param  array|string  $photo  Photo to send. Pass a file_id as String to send a photo that exists on the Telegram servers (recommended), pass an HTTP URL as a String for Telegram to get a photo from the Internet, or upload a new photo using multipart/form-data. The photo must be at most 10 MB in size. The photo's width and height must not exceed 10000 in total. Width and height ratio must be at most 20. More information on Sending Files »
     * @param  string  $caption  Photo caption (may also be used when resending photos by file_id), 0-1024 characters after entities parsing
     * @param  string  $parse_mode  Mode for parsing entities in the photo caption. See formatting options for more details.
     * @param  array  $reply_markup  Additional interface options. A JSON-serialized object for an inline keyboard, custom reply keyboard, instructions to remove a reply keyboard or to force a reply from the user.
     * @param  string  $business_connection_id  Unique identifier of the business connection on behalf of which the message will be sent
     * @param  int|string  $message_thread_id  Unique identifier for the target message thread (topic) of a forum; for forum supergroups and private chats of bots with forum topic mode enabled only
     * @param  int|string  $direct_messages_topic_id  Identifier of the direct messages topic to which the message will be sent; required if the message is sent to a direct messages chat
     * @param  array  $ephemeral_message_parameters  A JSON-serialized object containing the parameters of the ephemeral message to send
     * @param  \LaraGram\Laraquest\Updates\MessageEntity[]|array|string  $caption_entities  A JSON-serialized list of special entities that appear in the caption, which can be specified instead of parse_mode
     * @param  bool  $show_caption_above_media  Pass True if the caption must be shown above the message media
     * @param  bool  $has_spoiler  Pass True if the photo needs to be covered with a spoiler animation
     * @param  bool  $disable_notification  Sends the message silently. Users will receive a notification with no sound.
     * @param  bool  $protect_content  Protects the contents of the sent message from forwarding and saving
     * @param  bool  $allow_paid_broadcast  Pass True to allow up to 1000 messages per second, ignoring broadcasting limits for a fee of 0.1 Telegram Stars per message. The relevant Stars will be withdrawn from the bot's balance.
     * @param  string  $message_effect_id  Unique identifier of the message effect to be added to the message; for private chats only
     * @param  array  $suggested_post_parameters  A JSON-serialized object containing the parameters of the suggested post to send; for direct messages chats only. If the message is sent as a reply to another suggested post, then that suggested post is automatically declined.
     * @param  array  $reply_parameters  Description of the message to reply to
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function sendPhoto($photo, $caption = null, $parse_mode = null, $reply_markup = null, $business_connection_id = null, $message_thread_id = null, $direct_messages_topic_id = null, $ephemeral_message_parameters = null, $caption_entities = null, $show_caption_above_media = null, $has_spoiler = null, $disable_notification = null, $protect_content = null, $allow_paid_broadcast = null, $message_effect_id = null, $suggested_post_parameters = null, $reply_parameters = null): TelegramBroadcast
    {
        return $this->endpoint('sendPhoto', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to send a native poll. On success, the sent Message is returned.
     *
     * @param  string  $question  Poll question, 1-300 characters
     * @param  \LaraGram\Laraquest\Updates\InputPollOption[]|array|string  $options  A JSON-serialized list of 1-12 answer options
     * @param  array  $media  Media added to the poll description
     * @param  array  $reply_markup  Additional interface options. A JSON-serialized object for an inline keyboard, custom reply keyboard, instructions to remove a reply keyboard or to force a reply from the user.
     * @param  string  $business_connection_id  Unique identifier of the business connection on behalf of which the message will be sent
     * @param  int|string  $message_thread_id  Unique identifier for the target message thread (topic) of a forum; for forum supergroups and private chats of bots with forum topic mode enabled only
     * @param  string  $question_parse_mode  Mode for parsing entities in the question. See formatting options for more details. Currently, only custom emoji entities are allowed.
     * @param  \LaraGram\Laraquest\Updates\MessageEntity[]|array|string  $question_entities  A JSON-serialized list of special entities that appear in the poll question. It can be specified instead of question_parse_mode.
     * @param  bool  $is_anonymous  True, if the poll needs to be anonymous, defaults to True
     * @param  string  $type  Poll type, “quiz” or “regular”, defaults to “regular”
     * @param  bool  $allows_multiple_answers  Pass True if the poll allows multiple answers, defaults to False
     * @param  bool  $allows_revoting  Pass True if the poll allows to change chosen answer options, defaults to False for quizzes and to True for regular polls
     * @param  bool  $shuffle_options  Pass True if the poll options must be shown in random order
     * @param  bool  $allow_adding_options  Pass True if answer options can be added to the poll after creation; not supported for anonymous polls and quizzes
     * @param  bool  $hide_results_until_closes  Pass True if poll results must be shown only after the poll closes
     * @param  bool  $members_only  Pass True if voting is limited to users who have been members of the chat where the poll is being sent for more than 24 hours; for channel chats only
     * @param  string[]  $country_codes  A JSON-serialized list of 0-12 two-letter ISO 3166-1 alpha-2 country codes indicating the countries from which users can vote in the poll; for channel chats only. Use “FT” as a country code to allow users with anonymous numbers to vote. If omitted or empty, then users from any country can participate in the poll.
     * @param  int[]  $correct_option_ids  A JSON-serialized list of monotonically increasing 0-based identifiers of the correct answer options, required for polls in quiz mode
     * @param  string  $explanation  Text that is shown when a user chooses an incorrect answer or taps on the lamp icon in a quiz-style poll, 0-200 characters with at most 2 line feeds after entities parsing
     * @param  string  $explanation_parse_mode  Mode for parsing entities in the explanation. See formatting options for more details.
     * @param  \LaraGram\Laraquest\Updates\MessageEntity[]|array|string  $explanation_entities  A JSON-serialized list of special entities that appear in the poll explanation. It can be specified instead of explanation_parse_mode.
     * @param  array  $explanation_media  Media added to the quiz explanation
     * @param  int|string  $open_period  Amount of time in seconds the poll will be active after creation, 5-2628000. Can't be used together with close_date.
     * @param  int|string  $close_date  Point in time (Unix timestamp) when the poll will be automatically closed. Must be at least 5 and no more than 2628000 seconds in the future. Can't be used together with open_period.
     * @param  bool  $is_closed  Pass True if the poll needs to be immediately closed. This can be useful for poll preview.
     * @param  string  $description  Description of the poll to be sent, 0-1024 characters after entities parsing
     * @param  string  $description_parse_mode  Mode for parsing entities in the poll description. See formatting options for more details.
     * @param  \LaraGram\Laraquest\Updates\MessageEntity[]|array|string  $description_entities  A JSON-serialized list of special entities that appear in the poll description, which can be specified instead of description_parse_mode
     * @param  bool  $disable_notification  Sends the message silently. Users will receive a notification with no sound.
     * @param  bool  $protect_content  Protects the contents of the sent message from forwarding and saving
     * @param  bool  $allow_paid_broadcast  Pass True to allow up to 1000 messages per second, ignoring broadcasting limits for a fee of 0.1 Telegram Stars per message. The relevant Stars will be withdrawn from the bot's balance.
     * @param  string  $message_effect_id  Unique identifier of the message effect to be added to the message; for private chats only
     * @param  array  $reply_parameters  Description of the message to reply to
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function sendPoll($question, $options, $media = null, $reply_markup = null, $business_connection_id = null, $message_thread_id = null, $question_parse_mode = null, $question_entities = null, $is_anonymous = null, $type = null, $allows_multiple_answers = null, $allows_revoting = null, $shuffle_options = null, $allow_adding_options = null, $hide_results_until_closes = null, $members_only = null, $country_codes = null, $correct_option_ids = null, $explanation = null, $explanation_parse_mode = null, $explanation_entities = null, $explanation_media = null, $open_period = null, $close_date = null, $is_closed = null, $description = null, $description_parse_mode = null, $description_entities = null, $disable_notification = null, $protect_content = null, $allow_paid_broadcast = null, $message_effect_id = null, $reply_parameters = null): TelegramBroadcast
    {
        return $this->endpoint('sendPoll', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to send rich messages. If the message contains a block with a media element, then the bot must have the right to send the media to the chat. On success, the sent Message is returned.
     *
     * @param  array  $rich_message  The message to be sent
     * @param  array  $reply_markup  Additional interface options. A JSON-serialized object for an inline keyboard, custom reply keyboard, instructions to remove a reply keyboard or to force a reply from the user.
     * @param  string  $business_connection_id  Unique identifier of the business connection on behalf of which the message will be sent. Bot can send rich messages on behalf of a business account only if the corresponding user can send rich messages.
     * @param  int|string  $message_thread_id  Unique identifier for the target message thread (topic) of a forum; for forum supergroups and private chats of bots with forum topic mode enabled only
     * @param  int|string  $direct_messages_topic_id  Identifier of the direct messages topic to which the message will be sent; required if the message is sent to a direct messages chat
     * @param  array  $ephemeral_message_parameters  A JSON-serialized object containing the parameters of the ephemeral message to send
     * @param  bool  $disable_notification  Sends the message silently. Users will receive a notification with no sound.
     * @param  bool  $protect_content  Protects the contents of the sent message from forwarding and saving
     * @param  bool  $allow_paid_broadcast  Pass True to allow up to 1000 messages per second, ignoring broadcasting limits for a fee of 0.1 Telegram Stars per message. The relevant Stars will be withdrawn from the bot's balance.
     * @param  string  $message_effect_id  Unique identifier of the message effect to be added to the message; for private chats only
     * @param  array  $suggested_post_parameters  A JSON-serialized object containing the parameters of the suggested post to send; for direct messages chats only. If the message is sent as a reply to another suggested post, then that suggested post is automatically declined.
     * @param  array  $reply_parameters  Description of the message to reply to
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function sendRichMessage($rich_message, $reply_markup = null, $business_connection_id = null, $message_thread_id = null, $direct_messages_topic_id = null, $ephemeral_message_parameters = null, $disable_notification = null, $protect_content = null, $allow_paid_broadcast = null, $message_effect_id = null, $suggested_post_parameters = null, $reply_parameters = null): TelegramBroadcast
    {
        return $this->endpoint('sendRichMessage', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to stream a partial rich message to a user while the message is being generated. Note that the streamed draft is ephemeral and acts as a temporary 30-second preview - once the output is finalized, you must call sendRichMessage with the complete message to persist it in the user's chat. Returns True on success.
     *
     * @param  int|string  $draft_id  Unique identifier of the message draft; must be non-zero. Changes to drafts with the same identifier are animated. Otherwise, the draft is replaced without animation.
     * @param  array  $rich_message  The partial message to be streamed. Direct upload of new files and explicit upload of files by a URL isn't supported.
     * @param  int|string  $message_thread_id  Unique identifier for the target message thread
     * @param  bool  $can_stop  Pass True to show the user a button to stop further drafts. The bot will receive an Update “stopped_message_generation” if the user presses the button.
     * @param  bool  $keep_on_stop  Pass True to keep the draft in the chat when the button is pressed. The draft will still disappear after a short time or if the bot sends a message. To fully preserve the partial draft, the bot should send it as a new message.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function sendRichMessageDraft($draft_id, $rich_message, $message_thread_id = null, $can_stop = null, $keep_on_stop = null): TelegramBroadcast
    {
        return $this->endpoint('sendRichMessageDraft', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to send static .WEBP, animated .TGS, or video .WEBM stickers. On success, the sent Message is returned.
     *
     * @param  array|string  $sticker  Sticker to send. Pass a file_id as String to send a file that exists on the Telegram servers (recommended), pass an HTTP URL as a String for Telegram to get a .WEBP sticker from the Internet, or upload a new .WEBP, .TGS, or .WEBM sticker using multipart/form-data. More information on Sending Files ». Video and animated stickers can't be sent via an HTTP URL.
     * @param  array  $reply_markup  Additional interface options. A JSON-serialized object for an inline keyboard, custom reply keyboard, instructions to remove a reply keyboard or to force a reply from the user.
     * @param  string  $business_connection_id  Unique identifier of the business connection on behalf of which the message will be sent
     * @param  int|string  $message_thread_id  Unique identifier for the target message thread (topic) of a forum; for forum supergroups and private chats of bots with forum topic mode enabled only
     * @param  int|string  $direct_messages_topic_id  Identifier of the direct messages topic to which the message will be sent; required if the message is sent to a direct messages chat
     * @param  array  $ephemeral_message_parameters  A JSON-serialized object containing the parameters of the ephemeral message to send
     * @param  string  $emoji  Emoji associated with the sticker; only for just uploaded stickers
     * @param  bool  $disable_notification  Sends the message silently. Users will receive a notification with no sound.
     * @param  bool  $protect_content  Protects the contents of the sent message from forwarding and saving
     * @param  bool  $allow_paid_broadcast  Pass True to allow up to 1000 messages per second, ignoring broadcasting limits for a fee of 0.1 Telegram Stars per message. The relevant Stars will be withdrawn from the bot's balance.
     * @param  string  $message_effect_id  Unique identifier of the message effect to be added to the message; for private chats only
     * @param  array  $suggested_post_parameters  A JSON-serialized object containing the parameters of the suggested post to send; for direct messages chats only. If the message is sent as a reply to another suggested post, then that suggested post is automatically declined.
     * @param  array  $reply_parameters  Description of the message to reply to
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function sendSticker($sticker, $reply_markup = null, $business_connection_id = null, $message_thread_id = null, $direct_messages_topic_id = null, $ephemeral_message_parameters = null, $emoji = null, $disable_notification = null, $protect_content = null, $allow_paid_broadcast = null, $message_effect_id = null, $suggested_post_parameters = null, $reply_parameters = null): TelegramBroadcast
    {
        return $this->endpoint('sendSticker', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to send information about a venue. On success, the sent Message is returned.
     *
     * @param  float|string  $latitude  Latitude of the venue
     * @param  float|string  $longitude  Longitude of the venue
     * @param  string  $title  Name of the venue
     * @param  string  $address  Address of the venue
     * @param  array  $reply_markup  Additional interface options. A JSON-serialized object for an inline keyboard, custom reply keyboard, instructions to remove a reply keyboard or to force a reply from the user.
     * @param  string  $business_connection_id  Unique identifier of the business connection on behalf of which the message will be sent
     * @param  int|string  $message_thread_id  Unique identifier for the target message thread (topic) of a forum; for forum supergroups and private chats of bots with forum topic mode enabled only
     * @param  int|string  $direct_messages_topic_id  Identifier of the direct messages topic to which the message will be sent; required if the message is sent to a direct messages chat
     * @param  array  $ephemeral_message_parameters  A JSON-serialized object containing the parameters of the ephemeral message to send
     * @param  string  $foursquare_id  Foursquare identifier of the venue
     * @param  string  $foursquare_type  Foursquare type of the venue, if known. (For example, “arts_entertainment/default”, “arts_entertainment/aquarium” or “food/icecream”.)
     * @param  string  $google_place_id  Google Places identifier of the venue
     * @param  string  $google_place_type  Google Places type of the venue. (See supported types.)
     * @param  bool  $disable_notification  Sends the message silently. Users will receive a notification with no sound.
     * @param  bool  $protect_content  Protects the contents of the sent message from forwarding and saving
     * @param  bool  $allow_paid_broadcast  Pass True to allow up to 1000 messages per second, ignoring broadcasting limits for a fee of 0.1 Telegram Stars per message. The relevant Stars will be withdrawn from the bot's balance.
     * @param  string  $message_effect_id  Unique identifier of the message effect to be added to the message; for private chats only
     * @param  array  $suggested_post_parameters  A JSON-serialized object containing the parameters of the suggested post to send; for direct messages chats only. If the message is sent as a reply to another suggested post, then that suggested post is automatically declined.
     * @param  array  $reply_parameters  Description of the message to reply to
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function sendVenue($latitude, $longitude, $title, $address, $reply_markup = null, $business_connection_id = null, $message_thread_id = null, $direct_messages_topic_id = null, $ephemeral_message_parameters = null, $foursquare_id = null, $foursquare_type = null, $google_place_id = null, $google_place_type = null, $disable_notification = null, $protect_content = null, $allow_paid_broadcast = null, $message_effect_id = null, $suggested_post_parameters = null, $reply_parameters = null): TelegramBroadcast
    {
        return $this->endpoint('sendVenue', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to send video files, Telegram clients support MPEG4 videos (other formats may be sent as Document). On success, the sent Message is returned. Bots can currently send video files of up to 50 MB in size, this limit may be changed in the future.
     *
     * @param  array|string  $video  Video to send. Pass a file_id as String to send a video that exists on the Telegram servers (recommended), pass an HTTP URL as a String for Telegram to get a video from the Internet, or upload a new video using multipart/form-data. More information on Sending Files »
     * @param  string  $caption  Video caption (may also be used when resending videos by file_id), 0-1024 characters after entities parsing
     * @param  array|string  $thumbnail  Thumbnail of the file sent; can be ignored if thumbnail generation for the file is supported server-side. The thumbnail should be in JPEG format and less than 200 kB in size. A thumbnail's width and height should not exceed 320. Ignored if the file is not uploaded using multipart/form-data. Thumbnails can't be reused and can be only uploaded as a new file, so you can pass “attach://<file_attach_name>” if the thumbnail was uploaded using multipart/form-data under <file_attach_name>. More information on Sending Files »
     * @param  string  $parse_mode  Mode for parsing entities in the video caption. See formatting options for more details.
     * @param  array  $reply_markup  Additional interface options. A JSON-serialized object for an inline keyboard, custom reply keyboard, instructions to remove a reply keyboard or to force a reply from the user.
     * @param  string  $business_connection_id  Unique identifier of the business connection on behalf of which the message will be sent
     * @param  int|string  $message_thread_id  Unique identifier for the target message thread (topic) of a forum; for forum supergroups and private chats of bots with forum topic mode enabled only
     * @param  int|string  $direct_messages_topic_id  Identifier of the direct messages topic to which the message will be sent; required if the message is sent to a direct messages chat
     * @param  array  $ephemeral_message_parameters  A JSON-serialized object containing the parameters of the ephemeral message to send
     * @param  int|string  $duration  Duration of sent video in seconds
     * @param  int|string  $width  Video width
     * @param  int|string  $height  Video height
     * @param  array|string  $cover  Cover for the video in the message. Pass a file_id to send a file that exists on the Telegram servers (recommended), pass an HTTP URL for Telegram to get a file from the Internet, or pass “attach://<file_attach_name>” to upload a new one using multipart/form-data under <file_attach_name> name. More information on Sending Files »
     * @param  int|string  $start_timestamp  Start timestamp for the video in the message
     * @param  \LaraGram\Laraquest\Updates\MessageEntity[]|array|string  $caption_entities  A JSON-serialized list of special entities that appear in the caption, which can be specified instead of parse_mode
     * @param  bool  $show_caption_above_media  Pass True if the caption must be shown above the message media
     * @param  bool  $has_spoiler  Pass True if the video needs to be covered with a spoiler animation
     * @param  bool  $supports_streaming  Pass True if the uploaded video is suitable for streaming
     * @param  bool  $disable_notification  Sends the message silently. Users will receive a notification with no sound.
     * @param  bool  $protect_content  Protects the contents of the sent message from forwarding and saving
     * @param  bool  $allow_paid_broadcast  Pass True to allow up to 1000 messages per second, ignoring broadcasting limits for a fee of 0.1 Telegram Stars per message. The relevant Stars will be withdrawn from the bot's balance.
     * @param  string  $message_effect_id  Unique identifier of the message effect to be added to the message; for private chats only
     * @param  array  $suggested_post_parameters  A JSON-serialized object containing the parameters of the suggested post to send; for direct messages chats only. If the message is sent as a reply to another suggested post, then that suggested post is automatically declined.
     * @param  array  $reply_parameters  Description of the message to reply to
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function sendVideo($video, $caption = null, $thumbnail = null, $parse_mode = null, $reply_markup = null, $business_connection_id = null, $message_thread_id = null, $direct_messages_topic_id = null, $ephemeral_message_parameters = null, $duration = null, $width = null, $height = null, $cover = null, $start_timestamp = null, $caption_entities = null, $show_caption_above_media = null, $has_spoiler = null, $supports_streaming = null, $disable_notification = null, $protect_content = null, $allow_paid_broadcast = null, $message_effect_id = null, $suggested_post_parameters = null, $reply_parameters = null): TelegramBroadcast
    {
        return $this->endpoint('sendVideo', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to send a rounded square MPEG4 video of up to 1 minute long. On success, the sent Message is returned.
     *
     * @param  array|string  $video_note  Video note to send. Pass a file_id as String to send a video note that exists on the Telegram servers (recommended) or upload a new video using multipart/form-data. More information on Sending Files ». Sending video notes by a URL is currently unsupported.
     * @param  array|string  $thumbnail  Thumbnail of the file sent; can be ignored if thumbnail generation for the file is supported server-side. The thumbnail should be in JPEG format and less than 200 kB in size. A thumbnail's width and height should not exceed 320. Ignored if the file is not uploaded using multipart/form-data. Thumbnails can't be reused and can be only uploaded as a new file, so you can pass “attach://<file_attach_name>” if the thumbnail was uploaded using multipart/form-data under <file_attach_name>. More information on Sending Files »
     * @param  array  $reply_markup  Additional interface options. A JSON-serialized object for an inline keyboard, custom reply keyboard, instructions to remove a reply keyboard or to force a reply from the user.
     * @param  string  $business_connection_id  Unique identifier of the business connection on behalf of which the message will be sent
     * @param  int|string  $message_thread_id  Unique identifier for the target message thread (topic) of a forum; for forum supergroups and private chats of bots with forum topic mode enabled only
     * @param  int|string  $direct_messages_topic_id  Identifier of the direct messages topic to which the message will be sent; required if the message is sent to a direct messages chat
     * @param  array  $ephemeral_message_parameters  A JSON-serialized object containing the parameters of the ephemeral message to send
     * @param  int|string  $duration  Duration of sent video in seconds
     * @param  int|string  $length  Video width and height, i.e. diameter of the video message
     * @param  bool  $disable_notification  Sends the message silently. Users will receive a notification with no sound.
     * @param  bool  $protect_content  Protects the contents of the sent message from forwarding and saving
     * @param  bool  $allow_paid_broadcast  Pass True to allow up to 1000 messages per second, ignoring broadcasting limits for a fee of 0.1 Telegram Stars per message. The relevant Stars will be withdrawn from the bot's balance.
     * @param  string  $message_effect_id  Unique identifier of the message effect to be added to the message; for private chats only
     * @param  array  $suggested_post_parameters  A JSON-serialized object containing the parameters of the suggested post to send; for direct messages chats only. If the message is sent as a reply to another suggested post, then that suggested post is automatically declined.
     * @param  array  $reply_parameters  Description of the message to reply to
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function sendVideoNote($video_note, $thumbnail = null, $reply_markup = null, $business_connection_id = null, $message_thread_id = null, $direct_messages_topic_id = null, $ephemeral_message_parameters = null, $duration = null, $length = null, $disable_notification = null, $protect_content = null, $allow_paid_broadcast = null, $message_effect_id = null, $suggested_post_parameters = null, $reply_parameters = null): TelegramBroadcast
    {
        return $this->endpoint('sendVideoNote', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to send audio files, if you want Telegram clients to display the file as a playable voice message. For this to work, your audio must be in an .OGG file encoded with OPUS, or in .MP3 format, or in .M4A format (other formats may be sent as Audio or Document). On success, the sent Message is returned. Bots can currently send voice messages of up to 50 MB in size, this limit may be changed in the future.
     *
     * @param  array|string  $voice  Audio file to send. Pass a file_id as String to send a file that exists on the Telegram servers (recommended), pass an HTTP URL as a String for Telegram to get a file from the Internet, or upload a new one using multipart/form-data. More information on Sending Files »
     * @param  string  $caption  Voice message caption, 0-1024 characters after entities parsing
     * @param  string  $parse_mode  Mode for parsing entities in the voice message caption. See formatting options for more details.
     * @param  array  $reply_markup  Additional interface options. A JSON-serialized object for an inline keyboard, custom reply keyboard, instructions to remove a reply keyboard or to force a reply from the user.
     * @param  string  $business_connection_id  Unique identifier of the business connection on behalf of which the message will be sent
     * @param  int|string  $message_thread_id  Unique identifier for the target message thread (topic) of a forum; for forum supergroups and private chats of bots with forum topic mode enabled only
     * @param  int|string  $direct_messages_topic_id  Identifier of the direct messages topic to which the message will be sent; required if the message is sent to a direct messages chat
     * @param  array  $ephemeral_message_parameters  A JSON-serialized object containing the parameters of the ephemeral message to send
     * @param  \LaraGram\Laraquest\Updates\MessageEntity[]|array|string  $caption_entities  A JSON-serialized list of special entities that appear in the caption, which can be specified instead of parse_mode
     * @param  int|string  $duration  Duration of the voice message in seconds
     * @param  bool  $disable_notification  Sends the message silently. Users will receive a notification with no sound.
     * @param  bool  $protect_content  Protects the contents of the sent message from forwarding and saving
     * @param  bool  $allow_paid_broadcast  Pass True to allow up to 1000 messages per second, ignoring broadcasting limits for a fee of 0.1 Telegram Stars per message. The relevant Stars will be withdrawn from the bot's balance.
     * @param  string  $message_effect_id  Unique identifier of the message effect to be added to the message; for private chats only
     * @param  array  $suggested_post_parameters  A JSON-serialized object containing the parameters of the suggested post to send; for direct messages chats only. If the message is sent as a reply to another suggested post, then that suggested post is automatically declined.
     * @param  array  $reply_parameters  Description of the message to reply to
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function sendVoice($voice, $caption = null, $parse_mode = null, $reply_markup = null, $business_connection_id = null, $message_thread_id = null, $direct_messages_topic_id = null, $ephemeral_message_parameters = null, $caption_entities = null, $duration = null, $disable_notification = null, $protect_content = null, $allow_paid_broadcast = null, $message_effect_id = null, $suggested_post_parameters = null, $reply_parameters = null): TelegramBroadcast
    {
        return $this->endpoint('sendVoice', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to set a custom title for an administrator in a supergroup promoted by the bot. Returns True on success.
     *
     * @param  int|string  $user_id  Unique identifier of the target user
     * @param  string  $custom_title  New custom title for the administrator; 0-16 characters, emoji are not allowed
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function setChatAdministratorCustomTitle($user_id, $custom_title): TelegramBroadcast
    {
        return $this->endpoint('setChatAdministratorCustomTitle', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to change the description of a group, a supergroup or a channel. The bot must be an administrator in the chat for this to work and must have the appropriate administrator rights. Returns True on success.
     *
     * @param  string  $description  New chat description, 0-255 characters
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function setChatDescription($description = null): TelegramBroadcast
    {
        return $this->endpoint('setChatDescription', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to set a tag for a regular member in a group or a supergroup. The bot must be an administrator in the chat for this to work and must have the can_manage_tags administrator right. Returns True on success.
     *
     * @param  int|string  $user_id  Unique identifier of the target user
     * @param  string  $tag  New tag for the member; 0-16 characters, emoji are not allowed
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function setChatMemberTag($user_id, $tag = null): TelegramBroadcast
    {
        return $this->endpoint('setChatMemberTag', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to change the bot's menu button in a private chat, or the default menu button. Returns True on success.
     *
     * @param  array  $menu_button  A JSON-serialized object for the bot's new menu button. Defaults to MenuButtonDefault.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function setChatMenuButton($menu_button = null): TelegramBroadcast
    {
        return $this->endpoint('setChatMenuButton', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to set default chat permissions for all members. The bot must be an administrator in the group or a supergroup for this to work and must have the can_restrict_members administrator rights. Returns True on success.
     *
     * @param  array  $permissions  A JSON-serialized object for new default chat permissions
     * @param  bool  $use_independent_chat_permissions  Pass True if chat permissions are set independently. Otherwise, the can_send_other_messages and can_add_web_page_previews permissions will imply the can_send_messages, can_send_audios, can_send_documents, can_send_photos, can_send_videos, can_send_video_notes, and can_send_voice_notes permissions; the can_send_polls permission will imply the can_send_messages permission.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function setChatPermissions($permissions, $use_independent_chat_permissions = null): TelegramBroadcast
    {
        return $this->endpoint('setChatPermissions', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to set a new profile photo for the chat. Photos can't be changed for private chats. The bot must be an administrator in the chat for this to work and must have the appropriate administrator rights. Returns True on success.
     *
     * @param  array  $photo  New chat photo, uploaded using multipart/form-data
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function setChatPhoto($photo): TelegramBroadcast
    {
        return $this->endpoint('setChatPhoto', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to set a new group sticker set for a supergroup. The bot must be an administrator in the chat for this to work and must have the appropriate administrator rights. Use the field can_set_sticker_set optionally returned in getChat requests to check if the bot can use this method. Returns True on success.
     *
     * @param  string  $sticker_set_name  Name of the sticker set to be set as the group sticker set
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function setChatStickerSet($sticker_set_name): TelegramBroadcast
    {
        return $this->endpoint('setChatStickerSet', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to change the title of a chat. Titles can't be changed for private chats. The bot must be an administrator in the chat for this to work and must have the appropriate administrator rights. Returns True on success.
     *
     * @param  string  $title  New chat title, 1-128 characters
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function setChatTitle($title): TelegramBroadcast
    {
        return $this->endpoint('setChatTitle', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to set the score of the specified user in a game message. On success, if the message is not an inline message, the Message is returned, otherwise True is returned. Returns an error, if the new score is not greater than the user's current score in the chat and force is False.
     *
     * @param  int|string  $user_id  User identifier
     * @param  int|string  $score  New score, must be non-negative
     * @param  int|string  $message_id  Required if inline_message_id is not specified. Identifier of the sent message.
     * @param  bool  $force  Pass True if the high score is allowed to decrease. This can be useful when fixing mistakes or banning cheaters.
     * @param  bool  $disable_edit_message  Pass True if the game message should not be automatically edited to include the current scoreboard
     * @param  string  $inline_message_id  Required if chat_id and message_id are not specified. Identifier of the inline message.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function setGameScore($user_id, $score, $message_id = null, $force = null, $disable_edit_message = null, $inline_message_id = null): TelegramBroadcast
    {
        return $this->endpoint('setGameScore', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to change the access settings of a managed bot. Returns True on success.
     *
     * @param  bool  $is_access_restricted  Pass True if only selected users can access the bot. The bot's owner can always access it.
     * @param  int[]  $added_user_ids  A JSON-serialized list of up to 10 identifiers of users who will have access to the bot in addition to its owner. Ignored if is_access_restricted is False.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function setManagedBotAccessSettings($is_access_restricted, $added_user_ids = null): TelegramBroadcast
    {
        return $this->endpoint('setManagedBotAccessSettings', get_defined_vars(), 'user_id');
    }

    /**
     * Use this method to change the chosen reactions on a message. Service messages of some types can't be reacted to. Automatically forwarded messages from a channel to its discussion group have the same available reactions as messages in the channel. Bots can't use paid reactions. Returns True on success.
     *
     * @param  int|string  $message_id  Identifier of the target message. If the message belongs to a media group, the reaction is set to the first non-deleted message in the group instead.
     * @param  \LaraGram\Laraquest\Updates\ReactionType[]|array|string  $reaction  A JSON-serialized list of reaction types to set on the message. Currently, as non-premium users, bots can set up to one reaction per message. A custom emoji reaction can be used if it is either already present on the message or explicitly allowed by chat administrators. Paid reactions can't be used by bots.
     * @param  bool  $is_big  Pass True to set the reaction with a big animation
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function setMessageReaction($message_id, $reaction = null, $is_big = null): TelegramBroadcast
    {
        return $this->endpoint('setMessageReaction', get_defined_vars(), 'chat_id');
    }

    /**
     * Informs a user that some of the Telegram Passport elements they provided contains errors. The user will not be able to re-submit their Passport to you until the errors are fixed (the contents of the field for which you returned the error must change). Returns True on success. Use this if the data submitted by the user doesn't satisfy the standards your service requires for any reason. For example, if a birthday date seems invalid, a submitted document is blurry, a scan shows evidence of tampering, etc. Supply some details in the error message to make sure the user knows how to correct the issues.
     *
     * @param  \LaraGram\Laraquest\Updates\PassportElementError[]|array|string  $errors  A JSON-serialized Array describing the errors
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function setPassportDataErrors($errors): TelegramBroadcast
    {
        return $this->endpoint('setPassportDataErrors', get_defined_vars(), 'user_id');
    }

    /**
     * Use this method to set the thumbnail of a regular or mask sticker set. The format of the thumbnail file must match the format of the stickers in the set. Returns True on success.
     *
     * @param  string  $name  Sticker set name
     * @param  string  $format  Format of the thumbnail, must be one of “static” for a .WEBP or .PNG image, “animated” for a .TGS animation, or “video” for a .WEBM video
     * @param  array|string  $thumbnail  A .WEBP or .PNG image with the thumbnail, must be up to 128 kilobytes in size and have a width and height of exactly 100px, or a .TGS animation with a thumbnail up to 32 kilobytes in size (see https://core.telegram.org/stickers#animation-requirements for animated sticker technical requirements), or a .WEBM video with the thumbnail up to 32 kilobytes in size; see https://core.telegram.org/stickers#video-requirements for video sticker technical requirements. Pass a file_id as a String to send a file that already exists on the Telegram servers, pass an HTTP URL as a String for Telegram to get a file from the Internet, or upload a new one using multipart/form-data. More information on Sending Files ». Animated and video sticker set thumbnails can't be uploaded via HTTP URL. If omitted, then the thumbnail is dropped and the first sticker is used as the thumbnail.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function setStickerSetThumbnail($name, $format, $thumbnail = null): TelegramBroadcast
    {
        return $this->endpoint('setStickerSetThumbnail', get_defined_vars(), 'user_id');
    }

    /**
     * Changes the emoji status for a given user that previously allowed the bot to manage their emoji status via the Mini App method requestEmojiStatusAccess. Returns True on success.
     *
     * @param  string  $emoji_status_custom_emoji_id  Custom emoji identifier of the emoji status to set. Pass an empty string to remove the status.
     * @param  int|string  $emoji_status_expiration_date  Expiration date of the emoji status, if any
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function setUserEmojiStatus($emoji_status_custom_emoji_id = null, $emoji_status_expiration_date = null): TelegramBroadcast
    {
        return $this->endpoint('setUserEmojiStatus', get_defined_vars(), 'user_id');
    }

    /**
     * Use this method to stop updating a live location message before live_period expires. On success, if the message is not an inline message, the edited Message is returned, otherwise True is returned.
     *
     * @param  int|string  $message_id  Required if inline_message_id is not specified. Identifier of the message with live location to stop.
     * @param  array  $reply_markup  A JSON-serialized object for a new inline keyboard
     * @param  string  $business_connection_id  Unique identifier of the business connection on behalf of which the message to be edited was sent
     * @param  string  $inline_message_id  Required if chat_id and message_id are not specified. Identifier of the inline message.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function stopMessageLiveLocation($message_id = null, $reply_markup = null, $business_connection_id = null, $inline_message_id = null): TelegramBroadcast
    {
        return $this->endpoint('stopMessageLiveLocation', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to stop a poll which was sent by the bot. On success, the stopped Poll is returned.
     *
     * @param  int|string  $message_id  Identifier of the original message with the poll
     * @param  array  $reply_markup  A JSON-serialized object for a new message inline keyboard
     * @param  string  $business_connection_id  Unique identifier of the business connection on behalf of which the message to be edited was sent
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function stopPoll($message_id, $reply_markup = null, $business_connection_id = null): TelegramBroadcast
    {
        return $this->endpoint('stopPoll', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to unban a previously banned user in a supergroup or channel. The user will not return to the group or channel automatically, but will be able to join via link, etc. The bot must be an administrator for this to work. By default, this method guarantees that after the call the user is not a member of the chat, but will be able to join it. So if the user is a member of the chat they will also be removed from the chat. If you don't want this, use the parameter only_if_banned. Returns True on success.
     *
     * @param  int|string  $user_id  Unique identifier of the target user
     * @param  bool  $only_if_banned  Do nothing if the user is not banned
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function unbanChatMember($user_id, $only_if_banned = null): TelegramBroadcast
    {
        return $this->endpoint('unbanChatMember', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to unban a previously banned channel chat in a supergroup or channel. The bot must be an administrator for this to work and must have the appropriate administrator rights. Returns True on success.
     *
     * @param  int|string  $sender_chat_id  Unique identifier of the target sender chat
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function unbanChatSenderChat($sender_chat_id): TelegramBroadcast
    {
        return $this->endpoint('unbanChatSenderChat', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to unhide the 'General' topic in a forum supergroup chat. The bot must be an administrator in the chat for this to work and must have the can_manage_topics administrator rights. Returns True on success.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function unhideGeneralForumTopic(): TelegramBroadcast
    {
        return $this->endpoint('unhideGeneralForumTopic', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to clear the list of pinned messages in a chat. In private chats and channel direct messages chats, no additional rights are required to unpin all pinned messages. Conversely, the bot must be an administrator with the 'can_pin_messages' right or the 'can_edit_messages' right to unpin all pinned messages in groups and channels respectively. Returns True on success.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function unpinAllChatMessages(): TelegramBroadcast
    {
        return $this->endpoint('unpinAllChatMessages', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to clear the list of pinned messages in a forum topic in a forum supergroup chat or a private chat with a user. In the case of a supergroup chat the bot must be an administrator in the chat for this to work and must have the can_pin_messages administrator right in the supergroup. Returns True on success.
     *
     * @param  int|string  $message_thread_id  Unique identifier for the target message thread of the forum topic
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function unpinAllForumTopicMessages($message_thread_id): TelegramBroadcast
    {
        return $this->endpoint('unpinAllForumTopicMessages', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to clear the list of pinned messages in a General forum topic. The bot must be an administrator in the chat for this to work and must have the can_pin_messages administrator right in the supergroup. Returns True on success.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function unpinAllGeneralForumTopicMessages(): TelegramBroadcast
    {
        return $this->endpoint('unpinAllGeneralForumTopicMessages', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to remove a message from the list of pinned messages in a chat. In private chats and channel direct messages chats, all messages can be unpinned. Conversely, the bot must be an administrator with the 'can_pin_messages' right or the 'can_edit_messages' right to unpin messages in groups and channels respectively. Returns True on success.
     *
     * @param  int|string  $message_id  Identifier of the message to unpin. Required if business_connection_id is specified. If not specified, the most recent pinned message (by sending date) will be unpinned.
     * @param  string  $business_connection_id  Unique identifier of the business connection on behalf of which the message will be unpinned
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function unpinChatMessage($message_id = null, $business_connection_id = null): TelegramBroadcast
    {
        return $this->endpoint('unpinChatMessage', get_defined_vars(), 'chat_id');
    }

    /**
     * Use this method to upload a file with a sticker for later use in the createNewStickerSet, addStickerToSet, or replaceStickerInSet methods (the file can be used multiple times). Returns the uploaded File on success.
     *
     * @param  array  $sticker  A file with the sticker in .WEBP, .PNG, .TGS, or .WEBM format. See https://core.telegram.org/stickers for technical requirements. More information on Sending Files »
     * @param  string  $sticker_format  Format of the sticker, must be one of “static”, “animated”, “video”
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function uploadStickerFile($sticker, $sticker_format): TelegramBroadcast
    {
        return $this->endpoint('uploadStickerFile', get_defined_vars(), 'user_id');
    }

    /**
     * Verifies a chat on behalf of the organization which is represented by the bot. Returns True on success.
     *
     * @param  string  $custom_description  Custom description for the verification; 0-70 characters. Must be empty if the organization isn't allowed to provide a custom verification description.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function verifyChat($custom_description = null): TelegramBroadcast
    {
        return $this->endpoint('verifyChat', get_defined_vars(), 'chat_id');
    }

    /**
     * Verifies a user on behalf of the organization which is represented by the bot. Returns True on success.
     *
     * @param  string  $custom_description  Custom description for the verification; 0-70 characters. Must be empty if the organization isn't allowed to provide a custom verification description.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    public function verifyUser($custom_description = null): TelegramBroadcast
    {
        return $this->endpoint('verifyUser', get_defined_vars(), 'user_id');
    }

}
