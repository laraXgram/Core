<?php

namespace LaraGram\Conversation;

use LaraGram\Laraquest\Mode;
use LaraGram\Support\Facades\Cache;
use LaraGram\Support\Facades\Request;

class ConversationListener
{
    private array $questions;

    public function __construct()
    {
        $user_id = user()->id ?? callback_query()->from->id ?? '';
        $cacheKey = "conversation.$user_id";

        if (!Cache::has($cacheKey)) {
            return false;
        }

        $conversation_cache = json_decode(Cache::get($cacheKey), true);
        $conversationName = $conversation_cache['name'];

        if ((chat()->id ?? user()->id) != $conversation_cache['chatID']){
            return false;
        }

        if ($this->isConversationComplete($conversation_cache)) {
            return false;
        }

        if ($this->isConversationExpired($conversation_cache)) {
            $this->cancelConversation($conversationName, $cacheKey, 'EXPIRED');
            return false;
        }

        if ($this->isCancelCommand($conversation_cache)) {
            $this->cancelConversation($conversationName, $cacheKey, 'CANCEL_COMMAND');
            return false;
        }

        $this->questions = $conversation_cache['questions'];
        $step = $conversation_cache['step'];

        if (!$conversation_cache['waitForAnswer']) {
            $this->askQuestion($step);
            $conversation_cache['waitForAnswer'] = true;
            Cache::set($cacheKey, json_encode($conversation_cache));
        } else {
            $this->processAnswer($conversation_cache, $step, $cacheKey, $user_id);
        }

        return true;
    }

    private function isConversationExpired($conversation_cache): bool
    {
        return $conversation_cache['start'] + $conversation_cache['cancelTimeout'] < time();
    }

    private function isCancelCommand($conversation_cache): bool
    {
        return str_replace('/', '', message()->text ?? '') === $conversation_cache['cancelCommand'];
    }

    private function cancelConversation($conversationName, $cacheKey, $reason): void
    {
        $class = new (include app()->path("Conversations/{$conversationName}.php"));
        $class->onCancel(app('laraquest'), $reason);
        Cache::forgot($cacheKey);
    }

    private function processAnswer(&$conversation_cache, $step, $cacheKey, $user_id): void
    {
        $validated_answer = $this->validate($this->questions[$step]['type']);

        if ($validated_answer[0] || $this->isSkipCommand($step)) {
            $this->storeAnswer($conversation_cache, $validated_answer, $step);
            if ($this->isLastQuestion($conversation_cache)) {
                $this->completeConversation($conversation_cache, $cacheKey);
            } else {
                $this->askQuestion($conversation_cache['step']);
                Cache::set($cacheKey, json_encode($conversation_cache));
            }
        } else {
            $this->handleInvalidAnswer($conversation_cache, $step, $user_id);
            Cache::set($cacheKey, json_encode($conversation_cache));
        }
    }

    private function isSkipCommand($step): bool
    {
        return str_replace('/', '', message()->text) === $this->questions[$step]['skip_command'];
    }

    private function isConversationComplete($conversation_cache): bool
    {
        return isset($conversation_cache['complete']) && $conversation_cache['complete'];
    }

    private function storeAnswer(&$conversation_cache, $validated_answer, $step): void
    {
        $conversation_cache['answers'][$this->questions[$step]['name'] ?? $step] = $validated_answer[1] ?? 'SKIPPED';
        $conversation_cache['step']++;
        $conversation_cache['start'] = time();
    }

    private function isLastQuestion($conversation_cache): bool
    {
        return !isset($this->questions[$conversation_cache['step']]);
    }

    private function completeConversation(&$conversation_cache, $cacheKey): void
    {
        $class = new (include app()->path("Conversations/{$conversation_cache['name']}.php"));
        $class->onComplete(app('laraquest'), $conversation_cache['answers']);
        $conversation_cache['complete'] = true;

        if ($conversation_cache['forgot']) {
            Cache::forgot($cacheKey);
        }
    }

    private function handleInvalidAnswer(&$conversation_cache, $step, $user_id): void
    {
        if ($conversation_cache['totalAttempts'] >= $conversation_cache['maxAttempts']) {
            $this->cancelConversation($conversation_cache['name'], "conversation.$user_id", 'MAX_ATTEMPTS');
        } else {
            $this->askQuestion($step);
            $conversation_cache['totalAttempts']++;
        }
    }

    private function askQuestion($step): void
    {
        $allowed_media_types = ['document', 'animation', 'voice', 'audio', 'video', 'photo'];
        $question = $this->questions[$step];
        $media_type = strtolower($question['media'][0] ?? '');

        if (in_array($media_type, $allowed_media_types)) {
            $method = "send" . ucfirst($media_type);
            Request::mode(Mode::NO_RESPONSE_CURL)->$method(chat()->id ?? callback_query()->from->id, $question['media'][1] . $question['question'],
                reply_markup: $question['keyboard'] ?? null);
        } else {
            Request::mode(Mode::NO_RESPONSE_CURL)->sendMessage(chat()->id, $question['question'],
                reply_markup: $question['keyboard'] ?? null);
        }
    }

    private function validate($type): array
    {
        $message = message();
        $textOrCaption = $message->text ?? $message->caption ?? null;
        $callback_data = callback_query()->data ?? null;

        $checks = match ($type) {
            'keyboard' => [isset($callback_data), $callback_data],
            'string', 'text', 'caption' => [is_string($textOrCaption), $textOrCaption],
            'numeric' => [is_numeric($textOrCaption), $textOrCaption],
            'photo' => [isset($message->photo), $message->photo[0]->file_id],
            'video' => [isset($message->video), $message->video->file_id],
            'audio' => [isset($message->audio), $message->audio->file_id],
            'voice' => [isset($message->voice), $message->voice->file_id],
            'video_note' => [isset($message->video_note), $message->video_note->file_id],
            'venue' => [isset($message->venue), $message->venue->address],
            'contact' => [isset($message->contact), $message->contact->phone_number],
            'document' => [isset($message->document), $message->document->file_id],
            default => (function () use ($message, $callback_data) {
                if (isset($message)){
                    $validated = $this->validate(Request::getUpdateMessageSubType($message));
                    return [$validated[0], $validated[1]];
                }else{
                    return [isset($callback_data), $callback_data];
                }
            })(),
        };

        return [$checks[0] ?? false, $checks[1] ?? null];
    }
}
