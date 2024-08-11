<?php

if (!function_exists('selfDelete')) {
    function selfDelete(): void
    {
        request()->mode(LaraGram\Laraquest\Mode::NO_RESPONSE_CURL)->deleteMessage(chat()->id, message()->message_id);
    }
}