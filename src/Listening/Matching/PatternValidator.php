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

        $matcher = match ($method) {
            'TEXT' => function () use ($request, $regex, $pattern, $listenMethods) {
                if ($listenMethods == ['TEXT']) {
                    return preg_match($regex, text());
                } elseif ($listenMethods == ['TEXT', 'ENTITIES']) {
                    $entities = $request->message->entities ?? $request->message->caption_entities ?? [];
                    foreach ($entities as $entity) {
                        if ($entity->type == $pattern) {
                            return true;
                        }
                    }
                }

                return false;
            },
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
            'MESSAGE' => function () use ($request, $pattern, $listenMethods) {
                if ($listenMethods == ['MESSAGE']) {
                    if (!in_array($pattern, ['add_member', 'join_member'])) {
                        return isset($request->message->{$pattern});
                    } else {
                        if ($pattern == 'add_member' && isset($request->message->new_chat_members)) {
                            return $request->message->new_chat_members[0]->id != $request->message->from->id;
                        } elseif ($pattern == 'join_member' && isset($request->message->new_chat_members)) {
                            return $request->message->new_chat_members[0]->id == $request->message->from->id;
                        }
                    }
                } elseif ($listenMethods == ['MESSAGE', 'MESSAGE_TYPE']) {
                    $types = explode('|', $pattern);
                    foreach ($types as $type) {
                        if (isset($request->message->{$type})) {
                            return true;
                        }
                    }
                }

                return false;
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
