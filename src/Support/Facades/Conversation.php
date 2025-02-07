<?php

namespace LaraGram\Support\Facades;

/**
 * @method static create(callable $callback)
 * @method static start(string $name)
 * @method static getAnswers(int|string $user_id)
 * @method static getAnswer(int|string $user_id, string|int $name)
 * @method static void macro(string $name, callable $macro)
 * @method static bool hasMacro(string $name)
 */
class Conversation extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'conversation';
    }
}