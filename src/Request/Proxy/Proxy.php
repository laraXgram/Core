<?php

namespace LaraGram\Request\Proxy;

class Proxy
{
    /**
     * Proxy scheme => curl CURLPROXY_* constant.
     *
     * @var array<string, int>
     */
    protected const TYPES = [
        'http'    => CURLPROXY_HTTP,
        'https'   => CURLPROXY_HTTPS,
        'socks4'  => CURLPROXY_SOCKS4,
        'socks4a' => CURLPROXY_SOCKS4A,
        'socks5'  => CURLPROXY_SOCKS5,
        'socks5h' => CURLPROXY_SOCKS5_HOSTNAME,
    ];

    /**
     * @param  string       $name      A stable identifier for the proxy.
     * @param  string       $type      http | https | socks4 | socks4a | socks5 | socks5h
     * @param  string       $host      Hostname or IP.
     * @param  int          $port      TCP port.
     * @param  string|null  $username  Optional proxy auth username.
     * @param  string|null  $password  Optional proxy auth password.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly string $host,
        public readonly int $port,
        public readonly ?string $username = null,
        public readonly ?string $password = null,
    ) {
    }

    /**
     * Build a proxy from a URI string such as
     * "socks5://user:pass@127.0.0.1:1080" or "http://10.0.0.1:8080".
     *
     * @param  string       $uri
     * @param  string|null  $name  Optional explicit identifier.
     * @return static
     *
     * @throws \LaraGram\Request\Proxy\ProxyException
     */
    public static function fromString(string $uri, ?string $name = null): static
    {
        $parts = parse_url(trim($uri));

        if ($parts === false || ! isset($parts['host'])) {
            throw new ProxyException("Invalid proxy URI: [{$uri}].");
        }

        $type = strtolower($parts['scheme'] ?? 'http');

        return new static(
            name: $name ?? ($parts['host'] . ':' . ($parts['port'] ?? 0)),
            type: static::normalizeType($type),
            host: $parts['host'],
            port: (int) ($parts['port'] ?? 0),
            username: isset($parts['user']) ? rawurldecode($parts['user']) : null,
            password: isset($parts['pass']) ? rawurldecode($parts['pass']) : null,
        );
    }

    /**
     * Build a proxy from a configuration array.
     *
     * Accepts either a shorthand ['type://user:pass@host:port'] wrapped value,
     * or a full spec ['type' => .., 'host' => .., 'port' => .., ...].
     *
     * @param  array        $config
     * @param  string|null  $name
     * @return static
     *
     * @throws \LaraGram\Request\Proxy\ProxyException
     */
    public static function fromArray(array $config, ?string $name = null): static
    {
        if (isset($config['url']) && is_string($config['url'])) {
            return static::fromString($config['url'], $name ?? ($config['name'] ?? null));
        }

        if (! isset($config['host'], $config['port'])) {
            throw new ProxyException('Proxy config requires at least "host" and "port".');
        }

        return new static(
            name: $name ?? ($config['name'] ?? ($config['host'] . ':' . $config['port'])),
            type: static::normalizeType(strtolower($config['type'] ?? 'http')),
            host: (string) $config['host'],
            port: (int) $config['port'],
            username: $config['username'] ?? null,
            password: $config['password'] ?? null,
        );
    }

    /**
     * Coerce an arbitrary definition (string, array, or Proxy) into a Proxy.
     *
     * @param  \LaraGram\Request\Proxy\Proxy|array|string  $value
     * @param  string|null                                 $name
     * @return static
     */
    public static function make(Proxy|array|string $value, ?string $name = null): static
    {
        return match (true) {
            $value instanceof Proxy => $value,
            is_array($value)        => static::fromArray($value, $name),
            default                 => static::fromString($value, $name),
        };
    }

    /**
     * Validate & normalize a proxy scheme.
     *
     * @param  string  $type
     * @return string
     *
     * @throws \LaraGram\Request\Proxy\ProxyException
     */
    protected static function normalizeType(string $type): string
    {
        if (! isset(static::TYPES[$type])) {
            throw new ProxyException(
                "Unsupported proxy type [{$type}]. Supported: " . implode(', ', array_keys(static::TYPES)) . '.'
            );
        }

        return $type;
    }

    /**
     * The stable identifier for this proxy (used for state / lookups).
     *
     * @return string
     */
    public function id(): string
    {
        return $this->name;
    }

    /**
     * The curl CURLPROXY_* constant for this proxy's type.
     *
     * @return int
     */
    public function curlType(): int
    {
        return static::TYPES[$this->type];
    }

    /**
     * The "host:port" endpoint passed to CURLOPT_PROXY.
     *
     * @return string
     */
    public function endpoint(): string
    {
        return $this->host . ':' . $this->port;
    }

    /**
     * The "user:pass" credential passed to CURLOPT_PROXYUSERPWD, or null.
     *
     * @return string|null
     */
    public function credentials(): ?string
    {
        if ($this->username === null || $this->username === '') {
            return null;
        }

        return $this->username . ':' . ((string) $this->password);
    }

    /**
     * A redacted URI representation (credentials masked) for display / logs.
     *
     * @return string
     */
    public function display(): string
    {
        $auth = $this->username !== null && $this->username !== '' ? '***@' : '';

        return $this->type . '://' . $auth . $this->endpoint();
    }

    /**
     * Convert the proxy to an array (credentials masked unless $raw).
     *
     * @param  bool  $raw
     * @return array
     */
    public function toArray(bool $raw = false): array
    {
        return [
            'name'     => $this->name,
            'type'     => $this->type,
            'host'     => $this->host,
            'port'     => $this->port,
            'username' => $this->username,
            'password' => $raw ? $this->password : ($this->password !== null ? '***' : null),
        ];
    }
}
