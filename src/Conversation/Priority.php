<?php

namespace LaraGram\Conversation;

/**
 * Who handles an incoming update while a conversation is active.
 *
 *   Listen        - regular/step listens first; the conversation only handles
 *                   the update when no listen matches (the default).
 *   Conversation  - the current question handles the update before any listen.
 */
enum Priority
{
    case Listen;
    case Conversation;
}
