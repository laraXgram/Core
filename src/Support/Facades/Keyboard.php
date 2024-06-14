<?php

namespace LaraGram\Support\Facades;

/**
 * @method static \LaraGram\Keyboard\Keyboard replyKeyboardMarkup(array ...$rows)
 * @method static \LaraGram\Keyboard\Keyboard inlineKeyboardMarkup(array ...$rows)
 */
class Keyboard extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'keyboard';
    }
}