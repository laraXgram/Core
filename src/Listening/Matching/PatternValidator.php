<?php

namespace LaraGram\Listening\Matching;

use LaraGram\Listening\Contracts\ProvidesListenContext;
use LaraGram\Listening\Listen;
use LaraGram\Request\Request;
use LaraGram\Support\Str;

class PatternValidator implements ValidatorInterface
{
    /**
     * Validate a given rule against a listen and request.
     *
     * @param \LaraGram\Listening\Listen $listen
     * @param \LaraGram\Listening\Contracts\ProvidesListenContext $request
     * @return bool
     */
    public function matches(Listen $listen, ProvidesListenContext $request)
    {
        if (! $request instanceof Request) {
            return $this->matchesContext($listen, $request);
        }

        $listenMethods = $listen->methods();

        sort($listenMethods);
        if (
            $listenMethods == [
                "CALLBACK_DATA", "COMMAND", "DICE",
                "ENTITIES", "MESSAGE", "MESSAGE_TYPE",
                "REFERRAL", "TEXT", "UPDATE"
            ]
        ) {
            if ($listen->isStepListen()) {
                return $this->matchStepPattern($listen, $request);
            }

            return true;
        }

        if ($listen->isStepListen()) {
            return $this->matchStepPattern($listen, $request);
        }

        $method = $request->method();
        $regex = $listen->getCompiled()->getRegex();
        $pattern = $listen->pattern();

        // Container-scoped listeners (business / guest / edited / channel posts).
        // These are tagged with a secondary verb so they can be matched
        // regardless of the content-derived primary verb.
        if (($scoped = $this->matchScopedContainer($request, $listenMethods, $regex)) !== null) {
            return $scoped;
        }

        // Reaction listeners (emoji / custom_emoji / reaction type filtering).
        if (in_array('REACTION', $listenMethods, true)) {
            return $this->matchReaction($request, $pattern);
        }

        // Inline / chosen-inline query string listeners.
        if (in_array('QUERY', $listenMethods, true)) {
            $query = $request->inline_query->query
                ?? $request->chosen_inline_result->query
                ?? null;

            return $query !== null && (bool) preg_match($regex, $query);
        }

        // Rich message block-type listeners.
        if (in_array('RICH_TYPE', $listenMethods, true)) {
            return $this->matchRichType($pattern);
        }

        // Entity listeners - match against message entities or caption entities,
        // independent of the content-derived primary verb (text or captioned media).
        if (in_array('ENTITIES', $listenMethods, true)) {
            return $this->matchEntity($pattern);
        }

        $matcher = match ($method) {
            'TEXT' => fn () => (bool) preg_match($regex, text() ?? ''),
            'COMMAND' => function () use ($regex) {
                $text = Str::replaceFirst('/', '', text());
                return preg_match($regex, $text);
            },
            'REFERRAL' => function () use ($regex) {
                $text = Str::replaceFirst('/start ', '', text());
                return preg_match($regex, $text);
            },
            'DICE' => function () use ($pattern) {
                [$pEmoji, $pValue] = explode(',', $pattern);
                $emoji = message()->dice->emoji;
                $value = message()->dice->value;

                $emojiMatch =
                    $pEmoji == 'any' ||
                    $pEmoji == $emoji ||
                    in_array($emoji, explode('|', $pEmoji), true);

                $valueMatch =
                    $pValue == '0' ||
                    (is_numeric($pValue) && $pValue == $value) ||
                    in_array((string)$value, explode('|', $pValue), true);

                return $emojiMatch && $valueMatch;
            },
            'MESSAGE' => function () use ($pattern, $listenMethods) {
                $message = message();

                if (in_array('MESSAGE_TYPE', $listenMethods, true)) {
                    foreach (explode('|', $pattern) as $type) {
                        if (isset($message->{$type})) {
                            return true;
                        }
                    }

                    return false;
                }

                if ($pattern == 'add_member') {
                    return isset($message->new_chat_members)
                        && $message->new_chat_members[0]->id != $message->from->id;
                }

                if ($pattern == 'join_member') {
                    return isset($message->new_chat_members)
                        && $message->new_chat_members[0]->id == $message->from->id;
                }

                return isset($message->{$pattern});
            },
            'UPDATE' => function () use ($request, $pattern) {
                return isset($request->{$pattern});
            },
            'CALLBACK_DATA' => function () use ($regex) {
                return preg_match($regex, callback_query()->data ?? '');
            },
            default => function () {
                return false;
            }
        };

        return $matcher();
    }

    /**
     * Match a container-scoped listener (business/guest/edited/channel post).
     *
     * Listeners may be tagged with a secondary verb describing which Update
     * container they target:
     *   - ANY_<CONTAINER>  : matches any content inside that container.
     *   - TEXT_<CONTAINER> : matches the container's text against the pattern.
     *
     * The <CONTAINER> suffix maps directly to the Update field name, e.g.
     * ANY_BUSINESS_MESSAGE -> business_message, TEXT_EDITED_MESSAGE -> edited_message.
     *
     * @param  \LaraGram\Request\Request  $request
     * @param  array  $listenMethods
     * @param  string  $regex
     * @return bool|null  Null when the listener is not container-scoped.
     */
    protected function matchScopedContainer(Request $request, array $listenMethods, string $regex): ?bool
    {
        foreach ($listenMethods as $tag) {
            if (str_starts_with($tag, 'ANY_')) {
                $field = strtolower(substr($tag, 4));
                return isset($request->{$field});
            }

            if (str_starts_with($tag, 'TEXT_')) {
                $field = strtolower(substr($tag, 5));
                return isset($request->{$field})
                    && (bool) preg_match($regex, $request->{$field}->text ?? '');
            }
        }

        return null;
    }

    /**
     * Match an entity listener against the active message's entities.
     *
     * Scans both `entities` (text messages) and `caption_entities` (captioned
     * media) so entity listeners fire regardless of the message content type.
     *
     * @param  string  $pattern  The entity type to match (e.g. url, hashtag).
     * @return bool
     */
    protected function matchEntity(string $pattern): bool
    {
        $message = message();

        $entities = $message->entities ?? $message->caption_entities ?? [];

        foreach ($entities as $entity) {
            if (($entity->type ?? null) == $pattern) {
                return true;
            }
        }

        return false;
    }

    /**
     * Match a reaction listener against the incoming message_reaction update.
     *
     * The pattern is encoded as "<kind>::<value>" where kind is one of
     * emoji, custom_emoji or type.
     *
     * @param  \LaraGram\Request\Request  $request
     * @param  string  $pattern
     * @return bool
     */
    protected function matchReaction(Request $request, string $pattern): bool
    {
        [$kind, $value] = array_pad(explode('::', $pattern, 2), 2, '');

        $reactions = $request->message_reaction->new_reaction ?? [];

        foreach ($reactions as $reaction) {
            $type = $reaction->type ?? null;

            $matched = match ($kind) {
                'type' => $type === $value,
                'custom_emoji' => $type === 'custom_emoji',
                'emoji' => $type === 'emoji'
                    && ($value === 'any' || (bool) preg_match('/^(' . $value . ')$/u', $reaction->emoji ?? '')),
                default => false,
            };

            if ($matched) {
                return true;
            }
        }

        return false;
    }

    /**
     * Match a rich message listener against a given block type.
     *
     * @param  string  $pattern
     * @return bool
     */
    protected function matchRichType(string $pattern): bool
    {
        $rich = message()->rich_message ?? null;

        if ($rich === null) {
            return false;
        }

        $blocks = $rich->blocks ?? $rich->content ?? [];

        foreach ($blocks as $block) {
            if (($block->type ?? null) === $pattern) {
                return true;
            }
        }

        return false;
    }

    /**
     * Match a step listen's compiled pattern against the update content.
     *
     * Delegates to the same matching logic used by the main matches()
     * method, resolved by the real request method.
     *
     * @param  \LaraGram\Listening\Listen  $listen
     * @param  \LaraGram\Request\Request  $request
     * @return bool
     */
    protected function matchStepPattern(Listen $listen, Request $request): bool
    {
        $method  = $request->method();
        $regex   = $listen->getCompiled()->getRegex();
        $pattern = $listen->pattern();

        return match ($method) {
            'TEXT' => (bool) preg_match($regex, text() ?? ''),

            'COMMAND' => (bool) preg_match($regex, Str::replaceFirst('/', '', text() ?? '')),

            'REFERRAL' => (bool) preg_match($regex, Str::replaceFirst('/start ', '', text() ?? '')),

            'CALLBACK_DATA' => (bool) preg_match($regex, callback_query()->data ?? ''),

            'DICE' => (function () use ($pattern) {
                [$pEmoji, $pValue] = explode(',', $pattern);
                $emoji = message()->dice->emoji;
                $value = message()->dice->value;

                return ($pEmoji == 'any' || $pEmoji == $emoji || in_array($emoji, explode('|', $pEmoji), true))
                    && ($pValue == '0' || (is_numeric($pValue) && $pValue == $value) || in_array((string) $value, explode('|', $pValue), true));
            })(),

            'MESSAGE' => (function () use ($request, $pattern) {
                if ($pattern === 'add_member') {
                    return isset($request->message->new_chat_members)
                        && $request->message->new_chat_members[0]->id != $request->message->from->id;
                }

                if ($pattern === 'join_member') {
                    return isset($request->message->new_chat_members)
                        && $request->message->new_chat_members[0]->id == $request->message->from->id;
                }

                // Support pipe-separated types (e.g. "photo|voice|document")
                $types = explode('|', $pattern);
                foreach ($types as $type) {
                    if (isset($request->message->{$type})) {
                        return true;
                    }
                }

                return false;
            })(),

            'UPDATE' => isset($request->{$pattern}),

            default => false,
        };
    }

    /**
     * Match a listen's compiled pattern against a context-providing request
     * that is not the Bot-API Request (e.g. the MTProto ClientRequest).
     *
     * @param  \LaraGram\Listening\Listen  $listen
     * @param  \LaraGram\Listening\Contracts\ProvidesListenContext  $request
     * @return bool
     */
    protected function matchesContext(Listen $listen, ProvidesListenContext $request): bool
    {
        $verb  = $request->listenVerb();
        $value = $request->listenValue($verb);

        // Structural verb — no string to regex-match; verb match is enough.
        if ($value === null) {
            return true;
        }

        return (bool) preg_match($listen->getCompiled()->getRegex(), $value);
    }
}
