<?php

namespace LaraGram\Listening\Matching;

use LaraGram\Request\Request;
use LaraGram\Listening\Listen;

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
        $method = $request->method();

        $matcher = match ($method) {
            'TEXT' => function () use ($request, $listen) {
                return preg_match($listen->getCompiled()->getRegex(), text());
            },
//            'COMMAND' => function () use ($request, $listen) {
//                if ((message()->entities[0]->type ?? '') !== 'bot_command') {
//                    return false;
//                }
//
//                return preg_match($listen->getCompiled()->getRegex(), ltrim(text(), '/'));
//            },
//            'DICE' => function () use ($request, $listen) {
//                $emoji = message()->dice->emoji;
//                $value = message()->dice->value;
//
//
//            },
//            'MEDIA' => function () use ($request, $listen) {
//
//            },
//            'UPDATE' => function () use ($request, $listen) {
//
//            },
//            'MESSAGE' => function () use ($request, $listen) {
//
//            },
//            'MESSAGE_TYPE' => function () use ($request, $listen) {
//
//            },
//            'CALLBACK_DATA' => function () use ($request, $listen) {
//
//            },
//            'REFERRAL' => function () use ($request, $listen) {
//
//            },
//            'HASHTAG' => function () use ($request, $listen) {
//
//            },
//            'CASHTAG' => function () use ($request, $listen) {
//
//            },
//            'MENTION' => function () use ($request, $listen) {
//
//            },
//            'ADD_MEMBER' => function () use ($request, $listen) {
//
//            },
//            'JOIN_MEMBER' => function () use ($request, $listen) {
//
//            }
        };

        return $matcher();
//
//        file_put_contents('a.txt', 'test');
//        return true;
//        $path = rtrim($request->getPathInfo(), '/') ?: '/';
//
//        return preg_match($listen->getCompiled()->getRegex(), rawurldecode($path));
    }
}
