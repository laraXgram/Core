<?php

namespace LaraGram\Filesystem\Aws;

use Closure;
use LaraGram\Container\Container;
use LaraGram\Filesystem\Aws\Exceptions\AwsException;
use LaraGram\Http\Client\Core\Uri;
use LaraGram\Http\Client\Factory;
use LaraGram\Http\Client\PendingRequest;
use LaraGram\Http\Factory\RequestInterface;
use LaraGram\Http\Factory\ResponseInterface;
use LaraGram\Http\Factory\UriInterface;
use Throwable;

abstract class AwsClient
{
    /**
     * The region the client sends its requests to.
     *
     * @var string
     */
    protected string $region;

    /**
     * The endpoint the client sends its requests to.
     *
     * @var \LaraGram\Http\Factory\UriInterface
     */
    protected UriInterface $endpoint;

    /**
     * The credential provider, or false for anonymous requests.
     *
     * @var (\Closure(): \LaraGram\Filesystem\Aws\Credentials)|false
     */
    protected Closure|false $credentials;

    /**
     * The bearer token provider, used in place of SigV4 when present.
     *
     * @var (\Closure(): string)|null
     */
    protected ?Closure $token = null;

    /**
     * The request signer.
     *
     * @var \LaraGram\Filesystem\Aws\SignatureV4
     */
    protected SignatureV4 $signer;

    /**
     * Create a new AWS client instance.
     *
     * @param  array<string, mixed>  $config
     */
    public function __construct(protected array $config = [])
    {
        $this->region = (string) ($config['region']
            ?? CredentialProvider::env('AWS_REGION')
            ?? CredentialProvider::env('AWS_DEFAULT_REGION')
            ?? 'us-east-1');

        $this->endpoint = new Uri(rtrim((string) ($config['endpoint'] ?? $this->defaultEndpoint()), '/'));

        $this->credentials = $this->resolveCredentialProvider($config['credentials'] ?? null);

        $this->token = $this->resolveTokenProvider($config['token'] ?? null);

        $this->signer = new SignatureV4($this->signingName(), $this->region, $this->usesS3Signing());
    }

    /**
     * Get the name of the service used for signing.
     *
     * @return string
     */
    abstract protected function signingName(): string;

    /**
     * Get the endpoint used when none is configured.
     *
     * @return string
     */
    abstract protected function defaultEndpoint(): string;

    /**
     * Determine if the S3 signing rules apply to this client.
     *
     * @return bool
     */
    protected function usesS3Signing(): bool
    {
        return false;
    }

    /**
     * Get the client's region.
     *
     * @return string
     */
    public function getRegion(): string
    {
        return $this->region;
    }

    /**
     * Get the client's endpoint.
     *
     * @return \LaraGram\Http\Factory\UriInterface
     */
    public function getEndpoint(): UriInterface
    {
        return $this->endpoint;
    }

    /**
     * Get the client's configuration.
     *
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Resolve the credentials the client signs with, or null for anonymous requests.
     *
     * @return \LaraGram\Filesystem\Aws\Credentials|null
     */
    public function getCredentials(): ?Credentials
    {
        return $this->credentials === false ? null : ($this->credentials)();
    }

    /**
     * Resolve the credential provider from the "credentials" option.
     *
     * @param  mixed  $credentials
     * @return (\Closure(): \LaraGram\Filesystem\Aws\Credentials)|false
     */
    protected function resolveCredentialProvider(mixed $credentials): Closure|false
    {
        return match (true) {
            $credentials === false => false,
            $credentials instanceof Credentials => CredentialProvider::fromCredentials($credentials),
            is_array($credentials) => CredentialProvider::fromCredentials(Credentials::fromArray($credentials)),
            is_callable($credentials) => Closure::fromCallable($credentials),
            default => CredentialProvider::defaultProvider($this->config),
        };
    }

    /**
     * Resolve the bearer token provider from the "token" option.
     *
     * @param  mixed  $token
     * @return (\Closure(): string)|null
     */
    protected function resolveTokenProvider(mixed $token): ?Closure
    {
        if (is_array($token)) {
            $token = $token['token'] ?? null;
        }

        return match (true) {
            is_string($token) && $token !== '' => fn () => $token,
            is_callable($token) => Closure::fromCallable($token),
            default => null,
        };
    }

    /**
     * Sign the given request with the client's token or credentials.
     *
     * @param  \LaraGram\Http\Factory\RequestInterface  $request
     * @param  string|null  $payloadHash
     * @return \LaraGram\Http\Factory\RequestInterface
     */
    public function signRequest(RequestInterface $request, ?string $payloadHash = null): RequestInterface
    {
        foreach ((array) ($this->config['headers'] ?? []) as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if ($this->token) {
            return $request->withHeader('Authorization', 'Bearer '.($this->token)());
        }

        if ($credentials = $this->getCredentials()) {
            return $this->signer->signRequest($request, $credentials, $payloadHash);
        }

        return $request;
    }

    /**
     * Sign and send the given request, throwing an exception when AWS reports an error.
     *
     * @param  \LaraGram\Http\Factory\RequestInterface  $request
     * @param  bool  $stream
     * @param  string|null  $payloadHash
     * @return \LaraGram\Http\Factory\ResponseInterface
     *
     * @throws \LaraGram\Filesystem\Aws\Exceptions\AwsException
     */
    public function sendRequest(RequestInterface $request, bool $stream = false, ?string $payloadHash = null): ResponseInterface
    {
        $request = $this->signRequest($request, $payloadHash);

        $headers = [];

        foreach ($request->getHeaders() as $name => $values) {
            if (strtolower($name) !== 'host') {
                $headers[$name] = implode(', ', $values);
            }
        }

        $pending = $this->http()->withHeaders($headers);

        if ($stream) {
            $pending->withOptions(['stream' => true]);
        }

        $body = $request->getBody();

        if ($body->getSize() !== 0) {
            $pending->withBody($body, $request->getHeaderLine('Content-Type') ?: 'application/octet-stream');
        }

        try {
            $response = $pending->send($request->getMethod(), (string) $request->getUri())->toPsrResponse();
        } catch (Throwable $e) {
            throw new AwsException(
                'Error communicating with AWS ['.$this->signingName().']: '.$e->getMessage(), previous: $e
            );
        }

        if ($response->getStatusCode() >= 400) {
            throw $this->createException($request, $response);
        }

        return $response;
    }

    /**
     * Create a pending HTTP request with the client's HTTP options.
     *
     * @return \LaraGram\Http\Client\PendingRequest
     */
    protected function http(): PendingRequest
    {
        $container = Container::getInstance();

        $factory = $container->bound(Factory::class) ? $container->make(Factory::class) : new Factory;

        $options = (array) ($this->config['http'] ?? []);

        if (isset($this->config['timeout'])) {
            $options['timeout'] ??= $this->config['timeout'];
        }

        return $factory->withOptions($options);
    }

    /**
     * Create an exception for the given failed response.
     *
     * @param  \LaraGram\Http\Factory\RequestInterface  $request
     * @param  \LaraGram\Http\Factory\ResponseInterface  $response
     * @return \LaraGram\Filesystem\Aws\Exceptions\AwsException
     */
    protected function createException(RequestInterface $request, ResponseInterface $response): AwsException
    {
        [$code, $message, $requestId] = $this->parseError($response);

        $class = $this->exceptionClass();

        return new $class(
            sprintf(
                'Error executing [%s %s] on AWS [%s]: %d %s%s',
                $request->getMethod(),
                $request->getUri()->withQuery(''),
                $this->signingName(),
                $response->getStatusCode(),
                $code ?? $response->getReasonPhrase(),
                $message ? ' - '.$message : '',
            ),
            $code,
            $response->getStatusCode(),
            $requestId,
            $response,
            $message,
        );
    }

    /**
     * Get the exception class the client throws.
     *
     * @return class-string<\LaraGram\Filesystem\Aws\Exceptions\AwsException>
     */
    protected function exceptionClass(): string
    {
        return AwsException::class;
    }

    /**
     * Parse the error code, message and request ID from a JSON or XML error response.
     *
     * @param  \LaraGram\Http\Factory\ResponseInterface  $response
     * @return array{0: string|null, 1: string|null, 2: string|null}
     */
    protected function parseError(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();

        $requestId = $response->getHeaderLine('x-amzn-RequestId')
            ?: $response->getHeaderLine('x-amz-request-id') ?: null;

        $code = $response->getHeaderLine('x-amzn-ErrorType') ?: null;
        $message = null;

        if ($body !== '' && str_starts_with(ltrim($body), '<')) {
            $xml = @simplexml_load_string($body);

            if ($xml !== false) {
                $error = match (true) {
                    isset($xml->Error) => $xml->Error,
                    isset($xml->Errors->Error) => $xml->Errors->Error,
                    default => $xml,
                };

                $code ??= isset($error->Code) ? (string) $error->Code : null;
                $message = isset($error->Message) ? (string) $error->Message : null;
                $requestId ??= isset($xml->RequestId) ? (string) $xml->RequestId : (isset($error->RequestId) ? (string) $error->RequestId : null);
            }
        } elseif ($body !== '') {
            $data = json_decode($body, true);

            if (is_array($data)) {
                $code ??= $data['__type'] ?? $data['code'] ?? $data['Code'] ?? null;
                $message = $data['message'] ?? $data['Message'] ?? null;
            }
        }

        if ($code !== null) {
            $code = explode(':', $code)[0];
            $code = str_contains($code, '#') ? substr($code, strrpos($code, '#') + 1) : $code;
        } elseif ($body === '' && $response->getStatusCode() === 404) {
            $code = 'NotFound';
        }

        return [$code, is_string($message) ? $message : null, $requestId];
    }
}
