<?php

namespace LaraGram\Filesystem\Aws;

class Credentials
{
    /**
     * Create a new credentials instance.
     *
     * @param  string  $key
     * @param  string  $secret
     * @param  string|null  $token
     * @param  int|null  $expires
     */
    public function __construct(
        protected string $key,
        protected string $secret,
        protected ?string $token = null,
        protected ?int $expires = null,
    ) {
        //
    }

    /**
     * Create credentials from an array of "key", "secret", "token" and "expires" values.
     *
     * @param  array  $credentials
     * @return static
     */
    public static function fromArray(array $credentials): static
    {
        return new static(
            (string) ($credentials['key'] ?? ''),
            (string) ($credentials['secret'] ?? ''),
            $credentials['token'] ?? null,
            isset($credentials['expires']) ? (int) $credentials['expires'] : null,
        );
    }

    /**
     * Get the access key ID.
     *
     * @return string
     */
    public function getAccessKeyId(): string
    {
        return $this->key;
    }

    /**
     * Get the secret access key.
     *
     * @return string
     */
    public function getSecretKey(): string
    {
        return $this->secret;
    }

    /**
     * Get the session token, if any.
     *
     * @return string|null
     */
    public function getSecurityToken(): ?string
    {
        return $this->token;
    }

    /**
     * Get the UNIX timestamp at which the credentials expire, if any.
     *
     * @return int|null
     */
    public function getExpiration(): ?int
    {
        return $this->expires;
    }

    /**
     * Determine if the credentials have expired, or will within the given number of seconds.
     *
     * @param  int  $leeway
     * @return bool
     */
    public function isExpired(int $leeway = 0): bool
    {
        return $this->expires !== null && time() + $leeway >= $this->expires;
    }

    /**
     * Get the credentials as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'secret' => $this->secret,
            'token' => $this->token,
            'expires' => $this->expires,
        ];
    }
}
