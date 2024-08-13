<?php

namespace LaraGram\Support\Facades;

use LaraGram\Listener\Group;

/**
 * @method static on(array|string $message, callable|array|string $action)
 * @method static onText(array|string $message, callable|array|string $action)
 * @method static onCommand(array|string $command, callable|array|string $action)
 * @method static onAnimation(callable|array|string $action, array|string|null $file_id = null)
 * @method static onAudio(callable|array|string $action, array|string|null $file_id = null)
 * @method static onDocument(callable|array|string $action, array|string|null $file_id = null)
 * @method static onPhoto(callable|array|string $action, array|string|null $file_id = null)
 * @method static onSticker(callable|array|string $action, array|string|null $file_id = null)
 * @method static onVideo(callable|array|string $action, array|string|null $file_id = null)
 * @method static onVideoNote(callable|array|string $action, array|string|null $file_id = null)
 * @method static onVoice(callable|array|string $action, array|string|null $file_id = null)
 * @method static onContact(callable|array|string $action)
 * @method static onDice(callable|array|string $action, string|null $emoji = null, string|int|null $value = null)
 * @method static onGame(callable|array|string $action)
 * @method static onPoll(callable|array|string $action)
 * @method static onVenue(callable|array|string $action)
 * @method static onLocation(callable|array|string $action)
 * @method static onNewChatMembers(callable|array|string $action)
 * @method static onLeftChatMember(callable|array|string $action)
 * @method static onNewChatTitle(callable|array|string $action)
 * @method static onNewChatPhoto(callable|array|string $action)
 * @method static onDeleteChatPhoto(callable|array|string $action)
 * @method static onGroupChatCreated(callable|array|string $action)
 * @method static onSuperGroupChatCreated(callable|array|string $action)
 * @method static onMessageAutoDeleteTimerChanged(callable|array|string $action)
 * @method static onMigrateToChatId(callable|array|string $action)
 * @method static onMigrateFromChatId(callable|array|string $action)
 * @method static onPinnedMessage(callable|array|string $action)
 * @method static onInvoice(callable|array|string $action)
 * @method static onSuccessfulPayment(callable|array|string $action)
 * @method static onConnectedWebsite(callable|array|string $action)
 * @method static onPassportData(callable|array|string $action)
 * @method static onProximityAlertTriggered(callable|array|string $action)
 * @method static onForumTopicCreated(callable|array|string $action)
 * @method static onForumTopicEdited(callable|array|string $action)
 * @method static onForumTopicClosed(callable|array|string $action)
 * @method static onForumTopicReopened(callable|array|string $action)
 * @method static onVideoChatScheduled(callable|array|string $action)
 * @method static onVideoChatStarted(callable|array|string $action)
 * @method static onVideoChatEnded(callable|array|string $action)
 * @method static onVideoChatParticipantsInvited(callable|array|string $action)
 * @method static onWebAppData(callable|array|string $action)
 * @method static onMessage(callable|array|string $action)
 * @method static onMessageType(array|string $type, callable|array|string $action)
 * @method static onEditedMessage(callable|array|string $action)
 * @method static onChannelPost(callable|array|string $action)
 * @method static onEditedChannelPost(callable|array|string $action)
 * @method static onInlineQuery(callable|array|string $action)
 * @method static onChosenInlineResult(callable|array|string $action)
 * @method static onCallbackQuery(callable|array|string $action)
 * @method static onCallbackQueryData(array|string $pattern, callable|array|string $action)
 * @method static onShippingQuery(callable|array|string $action)
 * @method static onPreCheckoutQuery(callable|array|string $action)
 * @method static onPollAnswer(callable|array|string $action)
 * @method static onMyChatMember(callable|array|string $action)
 * @method static onChatMember(callable|array|string $action)
 * @method static onChatJoinRequest(callable|array|string $action)
 * @method static onReferral(callable|array|string $action)
 * @method static onAny(callable|array|string $action)
 * @method static onAddMember(callable|array|string $action)
 * @method static onJoinMember(callable|array|string $action)
 * @method static onMention(callable|array|string $action)
 * @method static onHashtag(callable|array|string $action)
 * @method static onCashtag(callable|array|string $action)
 * @method static group(array $options, callable $group)
 * @method static Group scope(array|string $scopes)
 * @method static Group outOfScope(array|string $scopes)
 * @method static Group can(array|string $roles)
 * @method static Group canNot(array|string $roles)
 * @method static Group controller(string $controller)
 */
class Bot extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'listener';
    }
}