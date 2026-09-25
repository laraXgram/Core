<?php

namespace LaraGram\Request;

use Closure;
use LaraGram\Contracts\Config\Repository;

class ConnectionResolver
{
    /**
     * The server variable that names the connection explicitly.
     *
     * It is not an HTTP header, so it can only be set by the web server or the
     * process feeding the update (a polling runner, a test), never by a client.
     *
     * @var string
     */
    public const SERVER_VARIABLE = 'LARAGRAM_BOT_CONNECTION';

    /**
     * The custom resolver callback, consulted before the built-in detection.
     *
     * @var (\Closure(\LaraGram\Request\Request, array<string, array>): (string|null))|null
     */
    protected static $resolver = null;

    /**
     * Create a new connection resolver instance.
     *
     * @param  \LaraGram\Contracts\Config\Repository  $config
     * @return void
     */
    public function __construct(protected Repository $config)
    {
        //
    }

    /**
     * Determine the bot connection the given update belongs to.
     *
     * With a fixed default connection that connection is returned. With 'auto'
     * the update is claimed by the connection it was provably sent to, in order:
     * an explicit server variable, the webhook secret token, the webhook URL.
     * Null is returned when no single connection can be told apart.
     *
     * @param  \LaraGram\Request\Request  $request
     * @return string|null
     */
    public function resolve(Request $request): ?string
    {
        if (! $this->isAuto()) {
            return blank($default = $this->config->get('bot.default')) ? null : (string) $default;
        }

        $connections = $this->connections();

        if (static::$resolver !== null &&
            $this->isConfigured($name = (static::$resolver)($request, $connections), $connections)) {
            return $name;
        }

        if ($this->isConfigured($name = $request->server()->get(static::SERVER_VARIABLE), $connections)) {
            return $name;
        }

        if (count($connections) === 1) {
            return (string) array_key_first($connections);
        }

        $candidates = $this->filterBySecretToken($connections, (string) $request->secretToken());

        if (count($candidates) > 1) {
            $candidates = $this->filterByWebhookUrl($candidates, $this->requestUri($request));
        }

        return count($candidates) === 1 ? (string) array_key_first($candidates) : null;
    }

    /**
     * Determine if the default connection is detected per update.
     *
     * @return bool
     */
    public function isAuto(): bool
    {
        return $this->config->get('bot.default') === 'auto';
    }

    /**
     * Keep the connections the secret token proves the update was sent to.
     *
     * Telegram sends the header on every update of a webhook registered with a
     * secret token, so a connection with a token only claims updates carrying
     * that exact token, and a connection without one only claims updates with
     * no token at all.
     *
     * @param  array<string, array>  $connections
     * @param  string  $token
     * @return array<string, array>
     */
    protected function filterBySecretToken(array $connections, string $token): array
    {
        return array_filter($connections, function ($config) use ($token) {
            $secret = (string) ($config['secret_token'] ?? '');

            return $secret === '' ? $token === '' : $token !== '' && hash_equals($secret, $token);
        });
    }

    /**
     * Keep the connections whose webhook URL the update was delivered to.
     *
     * @param  array<string, array>  $connections
     * @param  string|null  $uri
     * @return array<string, array>
     */
    protected function filterByWebhookUrl(array $connections, ?string $uri): array
    {
        if (blank($uri)) {
            return $connections;
        }

        $actual = parse_url($uri) ?: [];

        return array_filter($connections, fn ($config) => $this->matchesWebhookUrl((string) ($config['url'] ?? ''), $actual));
    }

    /**
     * Determine if a webhook URL points at the requested path and query.
     *
     * @param  string  $url
     * @param  array  $actual
     * @return bool
     */
    protected function matchesWebhookUrl(string $url, array $actual): bool
    {
        if ($url === '' || ($expected = parse_url($url)) === false) {
            return false;
        }

        if (rtrim($expected['path'] ?? '', '/') !== rtrim($actual['path'] ?? '', '/')) {
            return false;
        }

        parse_str($expected['query'] ?? '', $expectedQuery);
        parse_str($actual['query'] ?? '', $actualQuery);

        foreach ($expectedQuery as $key => $value) {
            if (($actualQuery[$key] ?? null) !== $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the URI the update was delivered to.
     *
     * @param  \LaraGram\Request\Request  $request
     * @return string|null
     */
    protected function requestUri(Request $request): ?string
    {
        $uri = $request->server()->get('REQUEST_URI');

        return is_string($uri) ? $uri : null;
    }

    /**
     * Get the configured bot connections.
     *
     * @return array<string, array>
     */
    protected function connections(): array
    {
        return array_filter((array) $this->config->get('bot.connections', []), 'is_array');
    }

    /**
     * Determine if the given name is a configured connection.
     *
     * @param  mixed  $name
     * @param  array<string, array>  $connections
     * @return bool
     */
    protected function isConfigured($name, array $connections): bool
    {
        return is_string($name) && $name !== '' && isset($connections[$name]);
    }

    /**
     * Register a custom callback that picks the connection of an update.
     *
     * It receives the request and the configured connections; returning null
     * (or an unknown name) falls back to the built-in detection.
     *
     * @param  (\Closure(\LaraGram\Request\Request, array<string, array>): (string|null))|null  $callback
     * @return void
     */
    public static function resolveUsing(?Closure $callback): void
    {
        static::$resolver = $callback;
    }
}
