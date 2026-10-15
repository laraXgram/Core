<?php

namespace LaraGram\Filesystem\Aws;

use DateTimeInterface;
use InvalidArgumentException;
use LaraGram\Http\Client\Core\PsrUtils;
use LaraGram\Http\Client\Core\Uri;
use LaraGram\Http\Factory\RequestInterface;

class SignatureV4
{
    /**
     * The signing algorithm.
     */
    public const ALGORITHM = 'AWS4-HMAC-SHA256';

    /**
     * The payload hash used when the body is not signed.
     */
    public const UNSIGNED_PAYLOAD = 'UNSIGNED-PAYLOAD';

    /**
     * The hash of an empty payload.
     */
    public const EMPTY_PAYLOAD = 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855';

    /**
     * The longest a presigned request may stay valid, in seconds.
     */
    public const MAX_EXPIRES = 604800;

    /**
     * The derived signing keys, keyed by date, region, service and secret.
     *
     * @var array<string, string>
     */
    protected array $signingKeys = [];

    /**
     * Create a new signer instance.
     *
     * @param  string  $service
     * @param  string  $region
     * @param  bool  $s3  Whether S3's signing rules apply (single URI encoding and a content hash header).
     */
    public function __construct(
        protected string $service,
        protected string $region,
        protected bool $s3 = false,
    ) {
        //
    }

    /**
     * Sign the given request with an Authorization header.
     *
     * @param  \LaraGram\Http\Factory\RequestInterface  $request
     * @param  \LaraGram\Filesystem\Aws\Credentials  $credentials
     * @param  string|null  $payloadHash
     * @return \LaraGram\Http\Factory\RequestInterface
     */
    public function signRequest(RequestInterface $request, Credentials $credentials, ?string $payloadHash = null): RequestInterface
    {
        $amzDate = gmdate('Ymd\THis\Z', $this->now());
        $shortDate = substr($amzDate, 0, 8);

        $payloadHash ??= $request->getHeaderLine('X-Amz-Content-Sha256') ?: $this->payloadHash($request);

        $request = $request
            ->withoutHeader('Authorization')
            ->withHeader('X-Amz-Date', $amzDate);

        if ($this->s3) {
            $request = $request->withHeader('X-Amz-Content-Sha256', $payloadHash);
        }

        if ($token = $credentials->getSecurityToken()) {
            $request = $request->withHeader('X-Amz-Security-Token', $token);
        }

        [$canonicalHeaders, $signedHeaders] = $this->canonicalHeaders($request);

        $scope = $this->scope($shortDate);

        $canonicalRequest = implode("\n", [
            strtoupper($request->getMethod()),
            $this->canonicalUri($request->getUri()->getPath()),
            $this->canonicalQuery($request->getUri()->getQuery()),
            $canonicalHeaders,
            $signedHeaders,
            $payloadHash,
        ]);

        $signature = $this->signature($canonicalRequest, $amzDate, $shortDate, $credentials);

        return $request->withHeader('Authorization', sprintf(
            '%s Credential=%s/%s, SignedHeaders=%s, Signature=%s',
            self::ALGORITHM, $credentials->getAccessKeyId(), $scope, $signedHeaders, $signature
        ));
    }

    /**
     * Presign the given request, moving the signature into its query string.
     *
     * @param  \LaraGram\Http\Factory\RequestInterface  $request
     * @param  \LaraGram\Filesystem\Aws\Credentials  $credentials
     * @param  \DateTimeInterface|int|string  $expires
     * @param  array{start_time?: \DateTimeInterface|int|string}  $options
     * @return \LaraGram\Http\Factory\RequestInterface
     */
    public function presign(RequestInterface $request, Credentials $credentials, DateTimeInterface|int|string $expires, array $options = []): RequestInterface
    {
        $start = isset($options['start_time']) ? $this->timestamp($options['start_time']) : $this->now();
        $expiresIn = $this->timestamp($expires) - $start;

        if ($expiresIn > self::MAX_EXPIRES) {
            throw new InvalidArgumentException('A presigned request may be valid for at most one week.');
        }

        $amzDate = gmdate('Ymd\THis\Z', $start);
        $shortDate = substr($amzDate, 0, 8);
        $scope = $this->scope($shortDate);

        $query = $this->parseQuery($request->getUri()->getQuery());

        // Amazon headers travel in the query string of a presigned URL, the rest must be sent by the client.
        foreach ($request->getHeaders() as $name => $values) {
            $lower = strtolower($name);

            if (str_starts_with($lower, 'x-amz-') && $lower !== 'x-amz-content-sha256') {
                $query[$name] = implode(',', $values);
                $request = $request->withoutHeader($name);
            }
        }

        $request = $request->withoutHeader('X-Amz-Content-Sha256')->withoutHeader('Authorization');

        [$canonicalHeaders, $signedHeaders] = $this->canonicalHeaders($request);

        $query['X-Amz-Algorithm'] = self::ALGORITHM;
        $query['X-Amz-Credential'] = $credentials->getAccessKeyId().'/'.$scope;
        $query['X-Amz-Date'] = $amzDate;
        $query['X-Amz-Expires'] = (string) $expiresIn;
        $query['X-Amz-SignedHeaders'] = $signedHeaders;

        if ($token = $credentials->getSecurityToken()) {
            $query['X-Amz-Security-Token'] = $token;
        }

        $queryString = $this->buildQuery($query);

        $canonicalRequest = implode("\n", [
            strtoupper($request->getMethod()),
            $this->canonicalUri($request->getUri()->getPath()),
            $queryString,
            $canonicalHeaders,
            $signedHeaders,
            self::UNSIGNED_PAYLOAD,
        ]);

        $query['X-Amz-Signature'] = $this->signature($canonicalRequest, $amzDate, $shortDate, $credentials);

        return $request->withUri($request->getUri()->withQuery($this->buildQuery($query)));
    }

    /**
     * Get the SHA-256 hash of the request payload.
     *
     * @param  \LaraGram\Http\Factory\RequestInterface  $request
     * @return string
     */
    public function payloadHash(RequestInterface $request): string
    {
        $body = $request->getBody();

        if ($body->getSize() === 0) {
            return self::EMPTY_PAYLOAD;
        }

        if (! $body->isSeekable()) {
            return self::UNSIGNED_PAYLOAD;
        }

        $hash = PsrUtils::hash($body, 'sha256');

        $body->rewind();

        return $hash;
    }

    /**
     * Calculate the signature of the given canonical request.
     *
     * @param  string  $canonicalRequest
     * @param  string  $amzDate
     * @param  string  $shortDate
     * @param  \LaraGram\Filesystem\Aws\Credentials  $credentials
     * @return string
     */
    protected function signature(string $canonicalRequest, string $amzDate, string $shortDate, Credentials $credentials): string
    {
        $stringToSign = implode("\n", [
            self::ALGORITHM,
            $amzDate,
            $this->scope($shortDate),
            hash('sha256', $canonicalRequest),
        ]);

        return hash_hmac('sha256', $stringToSign, $this->signingKey($shortDate, $credentials->getSecretKey()));
    }

    /**
     * Derive the signing key for the given date.
     *
     * @param  string  $shortDate
     * @param  string  $secret
     * @return string
     */
    protected function signingKey(string $shortDate, string $secret): string
    {
        $cacheKey = $shortDate.'_'.$this->region.'_'.$this->service.'_'.$secret;

        if (! isset($this->signingKeys[$cacheKey])) {
            if (count($this->signingKeys) > 50) {
                $this->signingKeys = [];
            }

            $dateKey = hash_hmac('sha256', $shortDate, 'AWS4'.$secret, true);
            $regionKey = hash_hmac('sha256', $this->region, $dateKey, true);
            $serviceKey = hash_hmac('sha256', $this->service, $regionKey, true);

            $this->signingKeys[$cacheKey] = hash_hmac('sha256', 'aws4_request', $serviceKey, true);
        }

        return $this->signingKeys[$cacheKey];
    }

    /**
     * Get the credential scope for the given date.
     *
     * @param  string  $shortDate
     * @return string
     */
    protected function scope(string $shortDate): string
    {
        return "{$shortDate}/{$this->region}/{$this->service}/aws4_request";
    }

    /**
     * Build the canonical URI of the given path.
     *
     * @param  string  $path
     * @return string
     */
    protected function canonicalUri(string $path): string
    {
        if ($path === '') {
            return '/';
        }

        if ($this->s3) {
            return $path;
        }

        return implode('/', array_map(rawurlencode(...), explode('/', $path)));
    }

    /**
     * Build the canonical query string.
     *
     * @param  string  $query
     * @return string
     */
    protected function canonicalQuery(string $query): string
    {
        return $this->buildQuery($this->parseQuery($query));
    }

    /**
     * Parse a query string without PHP's name mangling.
     *
     * @param  string  $query
     * @return array<string, string>
     */
    protected function parseQuery(string $query): array
    {
        $parameters = [];

        foreach ($query === '' ? [] : explode('&', $query) as $pair) {
            [$name, $value] = array_pad(explode('=', $pair, 2), 2, '');

            $parameters[rawurldecode($name)] = rawurldecode($value);
        }

        return $parameters;
    }

    /**
     * Build a sorted, RFC 3986 encoded query string.
     *
     * @param  array<string, string>  $parameters
     * @return string
     */
    protected function buildQuery(array $parameters): string
    {
        ksort($parameters, SORT_STRING);

        $pairs = [];

        foreach ($parameters as $name => $value) {
            $pairs[] = rawurlencode((string) $name).'='.rawurlencode((string) $value);
        }

        return implode('&', $pairs);
    }

    /**
     * Build the canonical headers block and the signed header list.
     *
     * Only the host and the Amazon headers are signed, so proxies and HTTP clients may still add their own.
     *
     * @param  \LaraGram\Http\Factory\RequestInterface  $request
     * @return array{0: string, 1: string}
     */
    protected function canonicalHeaders(RequestInterface $request): array
    {
        $headers = ['host' => $this->hostHeader($request)];

        foreach ($request->getHeaders() as $name => $values) {
            $lower = strtolower($name);

            if (str_starts_with($lower, 'x-amz-') || $lower === 'content-md5') {
                $headers[$lower] = implode(',', array_map(
                    fn ($value) => preg_replace('/\s+/', ' ', trim((string) $value)),
                    $values
                ));
            }
        }

        ksort($headers, SORT_STRING);

        $canonical = '';

        foreach ($headers as $name => $value) {
            $canonical .= $name.':'.$value."\n";
        }

        return [$canonical, implode(';', array_keys($headers))];
    }

    /**
     * Get the value of the Host header the request will be sent with.
     *
     * @param  \LaraGram\Http\Factory\RequestInterface  $request
     * @return string
     */
    protected function hostHeader(RequestInterface $request): string
    {
        $uri = $request->getUri();

        return $uri->getPort() === null || Uri::isDefaultPort($uri)
            ? $uri->getHost()
            : $uri->getHost().':'.$uri->getPort();
    }

    /**
     * Get the current UNIX timestamp.
     *
     * @return int
     */
    protected function now(): int
    {
        return time();
    }

    /**
     * Convert the given time to a UNIX timestamp.
     *
     * @param  \DateTimeInterface|int|string  $time
     * @return int
     */
    protected function timestamp(DateTimeInterface|int|string $time): int
    {
        return match (true) {
            $time instanceof DateTimeInterface => $time->getTimestamp(),
            is_int($time) => $time,
            default => (int) strtotime($time),
        };
    }
}
