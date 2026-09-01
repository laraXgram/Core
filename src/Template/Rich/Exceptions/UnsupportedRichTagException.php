<?php

namespace LaraGram\Template\Rich\Exceptions;

class UnsupportedRichTagException extends RichMessageException
{
    /**
     * Create an exception for a tag Telegram does not understand.
     *
     * @param  string  $tag
     * @return static
     */
    public static function tag(string $tag): static
    {
        return new static(
            "<{$tag}> is not supported in rich messages. See "
            .'https://core.telegram.org/bots/api#rich-html-style for the supported tags, '
            .'or call strict(false) to send it anyway.'
        );
    }
}
