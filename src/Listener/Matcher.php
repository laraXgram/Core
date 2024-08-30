<?php

namespace LaraGram\Listener;

use LaraGram\Request\Request;

class Matcher
{
    /** @var Request $request */
    private mixed $request;
    protected string $controller;

    public function match(string $type, \Closure|array|string $action, string|array|null $pattern)
    {
        $action = $this->getRealAction($action);
        $this->request = app('request');
        return $this->{"match_" . Type::findType($type)->value}($action, $pattern, $type);
    }

    private function execute_regex($pattern, $action, $input = null)
    {
        if ($input === null) {
            $input = $this->request->message->text ?? '';
        }

        $regex = $this->generateRegex($pattern);

        if (preg_match($regex, $input, $matches, PREG_UNMATCHED_AS_NULL)) {
            unset($matches[0]);
            $matches = array_filter($matches, function ($value) {
                return $value != null;
            });

            return call_user_func_array($action, [$this->request, ...$matches]);
        }

        return false;
    }

    private function match_text(callable $action, string|array $pattern)
    {
        if (is_array($pattern)) {
            foreach ($pattern as $value) {
                $result = $this->execute_regex($value, $action);
            }
            return $result;
        } else {
            return $this->execute_regex($pattern, $action);
        }

        return false;
    }

    private function match_command(callable $action, string|array $pattern)
    {
        if ($this->request->message->entities[0]->type !== 'bot_command') {
            return false;
        }

        if (is_array($pattern)) {
            foreach ($pattern as $value) {
                return $this->execute_regex($value, $action, ltrim($this->request->message->text, '/'));
            }
        } else {
            return $this->execute_regex($pattern, $action, ltrim($this->request->message->text, '/'));
        }

        return false;
    }

    private function match_media(callable $action, string|array|null $pattern, string $type)
    {
        if (isset($this->request->message->{$type})) {
            $message = $this->request->message->{$type};
            $type === 'photo' ? $file_id = $message[0]->file_unique_id : $file_id = $message->file_unique_id;
            if (is_array($pattern) && in_array($file_id, $pattern) || $file_id === $pattern || is_null($pattern) && isset($file_id)) {
                return call_user_func($action, $this->request);
            }
        }
        return false;
    }

    private function match_dice(callable $action, string|array $pattern)
    {
        $emoji = $this->request->message->dice->emoji;
        $value = $this->request->message->dice->value;

        $emoji_pattern = $pattern[0];
        $value_pattern = $pattern[1];

        if (
            (isset($emoji_pattern) && isset($value_pattern) && $emoji_pattern === $emoji && $value_pattern === $value) ||
            (isset($emoji_pattern) && !isset($value_pattern) && $emoji_pattern === $emoji) ||
            (isset($value_pattern) && !isset($emoji_pattern) && $value_pattern === $value) ||
            (!isset($emoji_pattern) && !isset($value_pattern))
        ) {
            return call_user_func_array($action, [$this->request, $emoji, $value]);
        }
        return false;
    }

    private function match_message(callable $action, null $pattern, string $type)
    {
        if (isset($this->request->message->{$type})) {
            return call_user_func($action, $this->request);
        }
        return false;
    }

    private function match_update(callable $action, null $pattern, string $type)
    {
        if (isset($this->request->{$type})) {
            return call_user_func($action, $this->request);
        }
        return false;
    }

    private function match_callback_query_data(callable $action, string|array $pattern)
    {
        if (isset($this->request->callback_query->data)) {
            if (is_array($pattern)) {
                foreach ($pattern as $patternItem) {
                    return $this->execute_regex($patternItem, $action, $this->request->callback_query->data);
                }
            } else {
                return $this->execute_regex($pattern, $action, $this->request->callback_query->data);
            }
        }
        return false;
    }

    private function match_message_type(callable $action, string|array $pattern)
    {
        if (is_array($pattern)) {
            foreach ($pattern as $patternItem) {
                if (strtolower($patternItem) == strtolower($this->request->getUpdateType())) {
                    return call_user_func($action, $this->request);
                }
            }
        } elseif (strtolower($pattern) == strtolower($this->request->getUpdateType())) {
            return call_user_func($action, $this->request);
        }
        return false;
    }

    private function match_referral(callable $action, null $pattern)
    {
        if (str_starts_with($this->request->message->text, '/start ')) {
            $text = str_replace('/start ', '', $this->request->message->text);
            return call_user_func_array($action, [$this->request, $text]);
        }
        return false;
    }

    private function match_any(callable $action, null $pattern)
    {
        if ($this->request->getData() !== null) {
            return call_user_func($action, $this->request);
        }
        return false;
    }

    private function match_hashtag(callable $action, null $pattern)
    {
        if (isset($this->request->message->entities)) {
            $entities = $this->request->message->entities;
            $text = $this->request->message->text;
        } elseif (isset($this->request->message->caption_entities)) {
            $entities = $this->request->message->caption_entities;
            $text = $this->request->message->caption;
        } else {
            return false;
        }

        $hashtags = [];
        foreach ($entities as $entity) {
            if ($entity->type === 'hashtag') {
                $offset = $entity->offset;
                $length = $entity->length;
                $hashtag = substr($text, $offset, $length);
                $hashtags[] = $hashtag;
            }
        }

        if ($hashtags != []) {
            return call_user_func_array($action, [$this->request, $hashtags]);
        } else {
            return false;
        }
    }

    private function match_mention(callable $action, null $pattern)
    {
        if (isset($this->request->message->entities)) {
            $entities = $this->request->message->entities;
            $text = $this->request->message->text;
        } elseif (isset($this->request->message->caption_entities)) {
            $entities = $this->request->message->caption_entities;
            $text = $this->request->message->caption;
        } else {
            return false;
        }

        $mentions = [];
        foreach ($entities as $entity) {
            if ($entity->type === 'mention') {
                $offset = $entity->offset;
                $length = $entity->length;
                $mention = substr($text, $offset, $length);
                $mentions[] = $mention;
            }
        }

        if ($mentions != []) {
            return call_user_func_array($action, [$this->request, $mentions]);
        } else {
            return false;
        }
    }

    private function match_cashtag(callable $action, null $pattern)
    {
        if (isset($this->request->message->entities)) {
            $entities = $this->request->message->entities;
            $text = $this->request->message->text;
        } elseif (isset($this->request->message->caption_entities)) {
            $entities = $this->request->message->caption_entities;
            $text = $this->request->message->caption;
        } else {
            return false;
        }

        $cashtags = [];
        foreach ($entities as $entity) {
            if ($entity->type === 'cashtag') {
                $offset = $entity->offset;
                $length = $entity->length;
                $cashtag = substr($text, $offset, $length);
                $cashtags[] = $cashtag;
            }
        }

        if ($cashtags != []) {
            return call_user_func_array($action, [$this->request, $cashtags]);
        } else {
            return false;
        }
    }

    private function match_add_member(callable $action, null $pattern)
    {
        if (isset($this->request->message->new_chat_members)){
            if ($this->request->message->new_chat_members[0]->id != $this->request->message->from->id){
                return call_user_func_array($action, [$this->request, $this->request->message->from->id, $this->request->message->new_chat_members[0]->id]);
            }
            return false;
        }
        return false;
    }

    private function match_join_member(callable $action, null $pattern)
    {
        if (isset($this->request->message->new_chat_members)){
            if ($this->request->message->new_chat_members[0]->id == $this->request->message->from->id){
                return call_user_func_array($action, [$this->request, $this->request->message->from->id]);
            }
            return false;
        }
        return false;
    }

    private function generateRegex(string $string): array|string|null
    {
        $pattern = "#\s*{(\w+)(\?)?\}#";
        $replacement = "\s*(\w+)$2";
        if (preg_match_all($pattern, $string, $matches, PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL)) {
            $lastMatch = end($matches[0]);
            $lastMatchPosition = $lastMatch[1];

            $beforeLastMatch = substr($string, 0, $lastMatchPosition);
            $afterLastMatch = substr($string, $lastMatchPosition + strlen($lastMatch[0]));

            $lastMatchReplaced = preg_replace($pattern, "\s*(.*)$2", $lastMatch[0], 1);

            $string = $beforeLastMatch . $lastMatchReplaced . $afterLastMatch;
        }

        return "/^" . preg_replace($pattern, $replacement, $string) . "$/";
    }

    private function getRealAction(\Closure|array|string $action)
    {
        if (is_callable($action) || $action instanceof \Closure) {
            return $action;
        }

        if (is_array($action)) {
            return [new $action[0], $action[1]];
        }

        if (is_string($action)) {
            if (str_contains($action, '@')) {
                $action = explode('@', $action);
                return [new $action[0], $action[1]];
            } else {
                if ($this->controller != '') {
                    return [new $this->controller, $action];
                }
                throw new \BadMethodCallException("Action not valid!");
            }
        }
    }
}