<?php

namespace LaraGram\Broadcasting\Telegram\Middleware;

use Closure;
use LaraGram\Contracts\Broadcasting\BroadcastStore;
use LaraGram\Contracts\Container\Container;
use LaraGram\Request\Request;
use Throwable;

class TrackChats
{
    /**
     * Chat member statuses meaning the bot left the chat or was blocked.
     *
     * @var array<int, string>
     */
    protected const GONE = ['kicked', 'left'];

    /**
     * Create a new middleware instance.
     *
     * @param  \LaraGram\Contracts\Container\Container  $app
     */
    public function __construct(protected Container $app)
    {
        //
    }

    /**
     * Record the chat of the update (and its members) so broadcasts can reach them.
     *
     * @param  \LaraGram\Request\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        try {
            $this->track($request);
        } catch (Throwable $e) {
            report($e);
        }

        return $response;
    }

    /**
     * Record the chat of the update.
     *
     * @param  \LaraGram\Request\Request  $request
     * @return void
     */
    protected function track(Request $request): void
    {
        $bot = $request->getConnection();

        $chats = $this->app->make(BroadcastStore::class);

        // The bot was added, removed, blocked or unblocked: always record it.
        if ($request->my_chat_member !== null) {
            $this->trackBotMembership($chats, $bot, $request->my_chat_member);

            return;
        }

        if ($request->chat_member !== null) {
            $this->trackMemberUpdate($chats, $bot, $request->chat_member);
        }

        if (is_null($chat = $this->chat($request)) || ! isset($chat->id, $chat->type)) {
            return;
        }

        if ($this->shouldTouch($bot, $chat->id)) {
            $chats->remember($bot, $chat->id, $chat->type, $this->attributes(
                $chat, $chat->type === 'private' ? $this->user($request) : null
            ));
        }

        if ($chat->type !== 'private' && $this->tracksMembers()) {
            $this->trackMessageMembers($chats, $bot, $chat, $request->message ?? $request->edited_message ?? null);
        }
    }

    /**
     * Record a change of the bot's own membership.
     *
     * @param  \LaraGram\Contracts\Broadcasting\BroadcastStore  $chats
     * @param  string  $bot
     * @param  object  $member
     * @return void
     */
    protected function trackBotMembership(BroadcastStore $chats, string $bot, object $member): void
    {
        $chat = $member->chat;
        $status = $member->new_chat_member->status ?? null;

        if (in_array($status, static::GONE, true)) {
            $chats->markUnreachable($bot, $chat->id, $status);
        } else {
            $chats->remember($bot, $chat->id, $chat->type, $this->attributes(
                $chat, $chat->type === 'private' ? ($member->from ?? null) : null
            ));
        }

        $this->forget($bot, $chat->id);
    }

    /**
     * Record a chat_member update (a user joined, left, was promoted or banned).
     *
     * @param  \LaraGram\Contracts\Broadcasting\BroadcastStore  $chats
     * @param  string  $bot
     * @param  object  $member
     * @return void
     */
    protected function trackMemberUpdate(BroadcastStore $chats, string $bot, object $member): void
    {
        $userId = $member->new_chat_member->user->id ?? null;
        $status = $member->new_chat_member->status ?? null;

        if ($this->tracksMembers() && $userId !== null && $status !== null && ! ($member->new_chat_member->user->is_bot ?? false)) {
            $chats->rememberMember($bot, $member->chat->id, $userId, $status);
        }
    }

    /**
     * Record the members seen in a group message: the sender, joins and leaves.
     *
     * @param  \LaraGram\Contracts\Broadcasting\BroadcastStore  $chats
     * @param  string  $bot
     * @param  object  $chat
     * @param  object|null  $message
     * @return void
     */
    protected function trackMessageMembers(BroadcastStore $chats, string $bot, object $chat, ?object $message): void
    {
        if ($message === null) {
            return;
        }

        foreach ((array) ($message->new_chat_members ?? []) as $user) {
            if (! ($user->is_bot ?? false)) {
                $chats->rememberMember($bot, $chat->id, $user->id, 'member');
            }
        }

        if (isset($message->left_chat_member->id) && ! ($message->left_chat_member->is_bot ?? false)) {
            $chats->rememberMember($bot, $chat->id, $message->left_chat_member->id, 'left');
        }

        $from = $message->from ?? null;

        if (isset($from->id) && ! ($from->is_bot ?? false) && ! isset($message->sender_chat) && $this->shouldTouch($bot, $chat->id.':'.$from->id)) {
            $chats->touchMember($bot, $chat->id, $from->id);
        }
    }

    /**
     * Get the chat the update belongs to.
     *
     * @param  \LaraGram\Request\Request  $request
     * @return object|null
     */
    protected function chat(Request $request): ?object
    {
        return match (true) {
            $request->message !== null => $request->message->chat,
            $request->edited_message !== null => $request->edited_message->chat,
            $request->channel_post !== null => $request->channel_post->chat,
            $request->callback_query !== null => $request->callback_query->message->chat ?? null,
            $request->chat_member !== null => $request->chat_member->chat,
            default => null,
        };
    }

    /**
     * Get the user who sent the update.
     *
     * @param  \LaraGram\Request\Request  $request
     * @return object|null
     */
    protected function user(Request $request): ?object
    {
        return $request->message->from
            ?? $request->edited_message->from
            ?? $request->callback_query->from
            ?? null;
    }

    /**
     * Get the attributes stored for the chat.
     *
     * @param  object  $chat
     * @param  object|null  $user
     * @return array
     */
    protected function attributes(object $chat, ?object $user): array
    {
        return array_filter([
            'title' => $chat->title ?? null,
            'username' => $chat->username ?? null,
            'first_name' => $chat->first_name ?? null,
            'last_name' => $chat->last_name ?? null,
            'language_code' => $chat->type === 'private' ? ($user->language_code ?? null) : null,
        ], fn ($value) => $value !== null);
    }

    /**
     * Determine if group members are recorded.
     *
     * @return bool
     */
    protected function tracksMembers(): bool
    {
        return (bool) $this->app->make('config')->get('broadcasting.tracking.members', true);
    }

    /**
     * Determine if the chat (or membership) was not recorded recently.
     *
     * @param  string  $bot
     * @param  int|string  $key
     * @return bool
     */
    protected function shouldTouch(string $bot, int|string $key): bool
    {
        $seconds = (int) $this->app->make('config')->get('broadcasting.tracking.touch_every', 3600);

        if ($seconds <= 0) {
            return true;
        }

        return $this->app->make('cache')->add($this->cacheKey($bot, $key), true, $seconds);
    }

    /**
     * Forget that the chat was recorded recently.
     *
     * @param  string  $bot
     * @param  int|string  $key
     * @return void
     */
    protected function forget(string $bot, int|string $key): void
    {
        $this->app->make('cache')->forget($this->cacheKey($bot, $key));
    }

    /**
     * Get the cache key marking a recently recorded chat.
     *
     * @param  string  $bot
     * @param  int|string  $key
     * @return string
     */
    protected function cacheKey(string $bot, int|string $key): string
    {
        return 'laragram:broadcast:chat:'.$bot.':'.$key;
    }
}
