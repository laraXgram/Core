<?php

namespace LaraGram\Auth\Status;

use LaraGram\Contracts\Auth\StatusProvider;
use LaraGram\Support\Facades\Request;

class LiveStatusProvider implements StatusProvider
{
    /**
     * The per-request resolved status cache.
     *
     * @var array<string, string|null>
     */
    protected $resolved = [];

    /**
     * Retrieve the chat-member status straight from the Telegram API.
     *
     * @param  int|string  $userId
     * @param  int|string  $chatId
     * @return string|null
     */
    public function get($userId, $chatId)
    {
        $key = $chatId.':'.$userId;

        if (array_key_exists($key, $this->resolved)) {
            return $this->resolved[$key];
        }

        $response = Request::getChatMember($chatId, $userId);

        return $this->resolved[$key] = $this->extractStatus($response);
    }

    /**
     * The live driver is read-only; persistence is a no-op.
     *
     * @param  int|string  $userId
     * @param  int|string  $chatId
     * @param  array  $attributes
     * @return void
     */
    public function put($userId, $chatId, array $attributes)
    {
        //
    }

    /**
     * Pull the status value out of a getChatMember response.
     *
     * @param  mixed  $response
     * @return string|null
     */
    protected function extractStatus($response)
    {
        if (is_array($response)) {
            return ($response['ok'] ?? false)
                ? ($response['result']['status'] ?? null)
                : null;
        }

        if (is_object($response)) {
            return $response->result->status ?? $response->status ?? null;
        }

        return null;
    }
}
