<?php

namespace LaraGram\Conversation\Middleware;

use Closure;
use LaraGram\Conversation\ConversationManager;
use LaraGram\Listening\Exceptions\ListenNotFoundException;
use LaraGram\Request\Response;

/**
 * Listen incoming updates to the active conversation before normal listens.
 */
class HandleConversation
{
    /**
     * The conversation manager instance.
     *
     * @var \LaraGram\Conversation\ConversationManager
     */
    protected $manager;

    /**
     * Create a new middleware instance.
     *
     * @param  \LaraGram\Conversation\ConversationManager  $manager
     * @return void
     */
    public function __construct(ConversationManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Handle an incoming update.
     *
     * @param  \LaraGram\Request\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($this->manager->prefersConversation($request) && $this->manager->handle($request)) {
            return new Response('');
        }

        try {
            return $next($request);
        } catch (ListenNotFoundException $e) {
            if ($this->manager->handle($request)) {
                return new Response('');
            }

            throw $e;
        }
    }
}
