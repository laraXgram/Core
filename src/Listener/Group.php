<?php

namespace LaraGram\Listener;

use LaraGram\Support\Facades\Request;

final class Group extends Listener
{
    private bool $condition = true;

    public function group(callable $callback)
    {
        if ($this->condition) {
            return $callback();
        }
        return false;
    }

    public function scope(array|string $scopes): static
    {
        $chat_type = chat()->type;
        $this->condition = is_array($scopes) ? in_array($chat_type, $scopes) : $chat_type == $scopes;
        return $this;
    }

    public function outOfScope(array|string $scopes): static
    {
        $chat_type = chat()->type;
        $this->condition = !(is_array($scopes) ? in_array($chat_type, $scopes) : $chat_type == $scopes);
        return $this;
    }

    public function middleware(array|string $middlewares)
    {

        return $this;
    }

    public function withoutMiddleware(array|string $middlewares)
    {

        return $this;
    }

    public function can(array|string $roles): static
    {
        $user_status = get_status();
        $this->condition = is_array($roles) ? in_array($user_status, $roles) : $user_status == $roles;
        return $this;
    }

    public function canNot(array|string $roles): static
    {
        $user_status = get_status();
        $this->condition = !(is_array($roles) ? in_array($user_status, $roles) : $user_status == $roles);
        return $this;
    }

    public function controller(string $controller): static
    {
        $this->controller = $controller;
        return $this;
    }
}