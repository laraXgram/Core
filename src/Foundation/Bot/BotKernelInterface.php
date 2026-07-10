<?php

namespace LaraGram\Foundation\Bot;

use LaraGram\Request\Request;
use LaraGram\Request\Response;

interface BotKernelInterface
{
    public const BOT_MAIN_REQUEST = 1;
    public const BOT_SUB_REQUEST = 2;

    /**
     * Handles a Request to convert it to a Response.
     *
     * When $catch is true, the implementation must catch all exceptions
     * and do its best to convert them to a Response instance.
     *
     * @param int  $type  The type of the request
     *                    (one of BotKernelInterface::BOT_MAIN_REQUEST or BotKernelInterface::BOT_SUB_REQUEST)
     * @param bool $catch Whether to catch exceptions or not
     *
     * @throws \Exception When an Exception occurs during processing
     */
    public function handleBot(Request $request, int $type = self::BOT_MAIN_REQUEST, bool $catch = true): Response;
}
