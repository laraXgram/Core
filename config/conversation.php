<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Conversation State Store
    |--------------------------------------------------------------------------
    |
    | The cache store used to persist conversation state. Set to null to use
    | the default cache store, or name any store defined in config/cache.php
    | (e.g. "redis", "array") to keep conversation state on a faster driver.
    |
    */

    'store' => env('CONVERSATION_STORE'),

    /*
    |--------------------------------------------------------------------------
    | Conversations Path
    |--------------------------------------------------------------------------
    |
    | The directory where conversation files live. Each file returns an
    | anonymous class extending LaraGram\Conversation\Conversation, the same
    | way migrations return an anonymous migration. Null uses app/Conversations.
    |
    */

    'path' => app_path('Conversation'),

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix & Lifetime
    |--------------------------------------------------------------------------
    |
    | The prefix applied to conversation cache keys and the maximum lifetime
    | (in seconds) a conversation's state is retained in the cache before it
    | naturally expires.
    |
    */

    'prefix' => 'conversation',

    'lifetime' => 3600,

    /*
    |--------------------------------------------------------------------------
    | Conversation Defaults
    |--------------------------------------------------------------------------
    |
    | Fallback values used when a conversation does not declare the matching
    | public property ($maxAttempts, $cancelCommand, $cancelTimeout,
    | $forgotAfterComplete). The timeout is the number of seconds of inactivity
    | after which a conversation is cancelled. Null disables a setting.
    |
    */

    'max_attempts' => 3,

    'cancel_command' => null,

    'cancel_timeout' => null,

    'forget_after_complete' => true,

    /*
    |--------------------------------------------------------------------------
    | Answer Feedback
    |--------------------------------------------------------------------------
    |
    | The message sent when an answer is rejected: true sends the validation
    | error, a string sends that text, and false sends nothing. The second
    | message is sent when an update matches none of a question's options.
    |
    */

    'retry_message' => true,

    'invalid_choice' => 'Please choose one of the options.',

    /*
    |--------------------------------------------------------------------------
    | Keyboard Clean Up
    |--------------------------------------------------------------------------
    |
    | Once a conversation is over, the inline keyboard of its last prompt is
    | taken back. A reply keyboard needs a message of its own to be removed,
    | which is only sent when the text below is not null.
    |
    */

    'clear_keyboard' => true,

    'keyboard_cleared_text' => null,

];
