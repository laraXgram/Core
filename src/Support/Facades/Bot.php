<?php

namespace LaraGram\Support\Facades;

/**
 * @method static on(array|string $message, callable $action)
 * @method static onText(array|string $message, callable $action)
 * @method static onCommand(array|string $command, callable $action)
 * @method static onAnimation(callable $action, array|string|null $file_id = null)
 * @method static onAudio(callable $action, array|string|null $file_id = null)
 * @method static onDocument(callable $action, array|string|null $file_id = null)
 * @method static onPhoto(callable $action, array|string|null $file_id = null)
 * @method static onSticker(callable $action, array|string|null $file_id = null)
 * @method static onVideo(callable $action, array|string|null $file_id = null)
 * @method static onVideoNote(callable $action, array|string|null $file_id = null)
 * @method static onVoice(callable $action, array|string|null $file_id = null)
 * @method static onContact(callable $action)
 * @method static onDice(callable $action, string|null $emoji = null, string|int|null $value = null)
 * @method static onGame(callable $action)
 * @method static onPoll(callable $action)
 * @method static onVenue(callable $action)
 * @method static onLocation(callable $action)
 * @method static onNewChatMembers(callable $action)
 * @method static onLeftChatMember(callable $action)
 * @method static onNewChatTitle(callable $action)
 * @method static onNewChatPhoto(callable $action)
 * @method static onDeleteChatPhoto(callable $action)
 * @method static onGroupChatCreated(callable $action)
 * @method static onSuperGroupChatCreated(callable $action)
 * @method static onMessageAutoDeleteTimerChanged(callable $action)
 * @method static onMigrateToChatId(callable $action)
 * @method static onMigrateFromChatId(callable $action)
 * @method static onPinnedMessage(callable $action)
 * @method static onInvoice(callable $action)
 * @method static onSuccessfulPayment(callable $action)
 * @method static onConnectedWebsite(callable $action)
 * @method static onPassportData(callable $action)
 * @method static onProximityAlertTriggered(callable $action)
 * @method static onForumTopicCreated(callable $action)
 * @method static onForumTopicEdited(callable $action)
 * @method static onForumTopicClosed(callable $action)
 * @method static onForumTopicReopened(callable $action)
 * @method static onVideoChatScheduled(callable $action)
 * @method static onVideoChatStarted(callable $action)
 * @method static onVideoChatEnded(callable $action)
 * @method static onVideoChatParticipantsInvited(callable $action)
 * @method static onWebAppData(callable $action)
 * @method static onMessage(callable $action)
 * @method static onMessageType(array|string $type, callable $action)
 * @method static onEditedMessage(callable $action)
 * @method static onChannelPost(callable $action)
 * @method static onEditedChannelPost(callable $action)
 * @method static onInlineQuery(callable $action)
 * @method static onChosenInlineResult(callable $action)
 * @method static onCallbackQuery(callable $action)
 * @method static onCallbackQueryData(array|string $pattern, callable $action)
 * @method static onShippingQuery(callable $action)
 * @method static onPreCheckoutQuery(callable $action)
 * @method static onPollAnswer(callable $action)
 * @method static onMyChatMember(callable $action)
 * @method static onChatMember(callable $action)
 * @method static onChatJoinRequest(callable $action)
 * @method static onReferral(callable $action)
 * @method static onAny(callable $action)
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