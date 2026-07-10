<?php

namespace LaraGram\Foundation\Http;

use LaraGram\Support\Tempora;
use LaraGram\Cookie\Cookie;

class MaintenanceModeBypassCookie
{
    /**
     * Create a new maintenance mode bypass cookie.
     *
     * @param  string  $key
     * @return \LaraGram\Cookie\Cookie
     */
    public static function create(string $key)
    {
        $expiresAt = Tempora::now()->addHours(12);

        return new Cookie('laragram_maintenance', base64_encode(json_encode([
            'expires_at' => $expiresAt->getTimestamp(),
            'mac' => hash_hmac('sha256', $expiresAt->getTimestamp(), $key),
        ])), $expiresAt, config('session.path'), config('session.domain'));
    }

    /**
     * Determine if the given maintenance mode bypass cookie is valid.
     *
     * @param  string  $cookie
     * @param  string  $key
     * @return bool
     */
    public static function isValid(string $cookie, string $key)
    {
        $payload = json_decode(base64_decode($cookie), true);

        return is_array($payload) &&
            is_numeric($payload['expires_at'] ?? null) &&
            isset($payload['mac']) &&
            hash_equals(hash_hmac('sha256', $payload['expires_at'], $key), $payload['mac']) &&
            (int) $payload['expires_at'] >= Tempora::now()->getTimestamp();
    }
}
