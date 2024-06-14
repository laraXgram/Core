<?php

namespace LaraGram\Listener;

class Matcher
{
    private mixed $request;

    public function match(string $type, callable $action, string|array|null $pattern)
    {
        $this->request = app('request');
        return $this->{"match_" . Type::findType($type)->value}($action, $pattern, $type);
    }

    private function execute_regex($pattern, $action, $input = null)
    {
        if ($input === null) {
            $input = $this->request->message->text ?? '';
        }

        $regex = preg_replace('/{((?:(?!\d+,?\d?+)\w)+?)}/', '(?:\s+(?<$1>.*))', $pattern);
        $regex = preg_replace('/{((?:(?!\d+,?\d?+)\w)+?)\?}/', '(?:\s+(?<$1>.*)?)', $regex);
        $regex = str_replace(' ', '\s*', '/^' . $regex . '?$/imUu');

        if (preg_match($regex, $input, $matches, PREG_UNMATCHED_AS_NULL)) {
            $matches = array_filter($matches, function ($value, $key) {
                return !is_numeric($key) && $value !== "" && $value !== null;
            }, ARRAY_FILTER_USE_BOTH);
            return call_user_func_array($action, [$this->request, $matches]);
        }

        return false;
    }

    private function match_text(callable $action, string|array $pattern)
    {
        if (is_array($pattern)) {
            foreach ($pattern as $value) {
                return $this->execute_regex($value, $action);
            }
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

    private function matchDice(callable $action, string|array $pattern)
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
        if ($this->request->callback_query->data !== null) {
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
        if (str_starts_with($this->request->message->text, '/start ')){
            $text = str_replace('/start ',  '', $this->request->message->text);
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
}