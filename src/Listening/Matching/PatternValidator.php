<?php

namespace LaraGram\Listening\Matching;

use LaraGram\Listening\Listen;
use LaraGram\Request\Request;

class PatternValidator implements ValidatorInterface
{
    /**
     * Validate a given rule against a listen and request.
     *
     * @param \LaraGram\Listening\Listen $listen
     * @param \LaraGram\Request\Request $request
     * @return bool
     */
    public function matches(Listen $listen, Request $request)
    {
        if ($listen->methods() == [
                'TEXT', 'DICE', 'UPDATE', 'MESSAGE', 'MESSAGE_TYPE',
                'CALLBACK_DATA', 'ENTITIES', 'REFERRAL',
            ]) return true;

        $method = $request->method();
        $regex = $listen->getCompiled()->getRegex();
        $pattern = $listen->pattern();
        $listenMethods = $listen->methods();

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
                $text = ltrim(text(), '/');
                return preg_match($regex, $text);
            },
            'REFERRAL' => function () {
                return true;
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
}
