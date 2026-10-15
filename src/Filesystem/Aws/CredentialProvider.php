<?php

namespace LaraGram\Filesystem\Aws;

use Closure;
use LaraGram\Container\Container;
use LaraGram\Filesystem\Aws\Exceptions\CredentialsException;
use LaraGram\Filesystem\Aws\Sts\StsClient;
use LaraGram\Http\Client\Factory;
use Throwable;

class CredentialProvider
{
    /**
     * The address of the EC2 instance metadata service.
     */
    public const IMDS_ENDPOINT = 'http://169.254.169.254';

    /**
     * The address of the ECS container credentials endpoint.
     */
    public const ECS_ENDPOINT = 'http://169.254.170.2';

    /**
     * The number of seconds before expiration at which memoized credentials are refreshed.
     */
    public const REFRESH_WINDOW = 300;

    /**
     * Create the default credential provider chain.
     *
     * Credentials are looked up in the environment, a web identity token, the shared
     * credentials and config files, the ECS container endpoint, and finally the EC2
     * instance metadata service. The first source that has credentials wins.
     *
     * @param  array<string, mixed>  $config
     * @return \Closure(): \LaraGram\Filesystem\Aws\Credentials
     */
    public static function defaultProvider(array $config = []): Closure
    {
        $timeout = (float) ($config['http']['timeout'] ?? $config['timeout'] ?? 1.0);

        return static::memoize(static::chain(
            static::envProvider(),
            static::webIdentity($config),
            static::ini($config['profile'] ?? null),
            static::ini($config['profile'] ?? null, static::configFile(), true),
            static::ecs($timeout),
            static::instanceProfile($timeout),
        ));
    }

    /**
     * Create a provider that always returns the given credentials.
     *
     * @param  \LaraGram\Filesystem\Aws\Credentials  $credentials
     * @return \Closure(): \LaraGram\Filesystem\Aws\Credentials
     */
    public static function fromCredentials(Credentials $credentials): Closure
    {
        return fn () => $credentials;
    }

    /**
     * Create a provider that tries each of the given providers in turn.
     *
     * Every provider returns credentials or null; the chain throws when none of them has any.
     *
     * @param  (\Closure(): (\LaraGram\Filesystem\Aws\Credentials|null))  ...$providers
     * @return \Closure(): \LaraGram\Filesystem\Aws\Credentials
     */
    public static function chain(Closure ...$providers): Closure
    {
        return function () use ($providers) {
            $errors = [];

            foreach ($providers as $provider) {
                try {
                    if ($credentials = $provider()) {
                        return $credentials;
                    }
                } catch (Throwable $e) {
                    $errors[] = $e->getMessage();
                }
            }

            throw new CredentialsException(
                'Unable to resolve AWS credentials from the environment, a web identity token, the shared credentials files, ECS or EC2 instance metadata.'
                .($errors ? ' '.implode(' ', $errors) : '')
            );
        };
    }

    /**
     * Wrap the given provider so its credentials are reused until shortly before they expire.
     *
     * @param  \Closure(): \LaraGram\Filesystem\Aws\Credentials  $provider
     * @return \Closure(): \LaraGram\Filesystem\Aws\Credentials
     */
    public static function memoize(Closure $provider): Closure
    {
        $credentials = null;

        return function () use ($provider, &$credentials) {
            if ($credentials === null || $credentials->isExpired(static::REFRESH_WINDOW)) {
                $credentials = $provider();
            }

            return $credentials;
        };
    }

    /**
     * Create a provider that reads the AWS_ACCESS_KEY_ID family of environment variables.
     *
     * @return \Closure(): (\LaraGram\Filesystem\Aws\Credentials|null)
     */
    public static function envProvider(): Closure
    {
        return function () {
            $key = static::env('AWS_ACCESS_KEY_ID');
            $secret = static::env('AWS_SECRET_ACCESS_KEY');

            return $key && $secret
                ? new Credentials($key, $secret, static::env('AWS_SESSION_TOKEN'))
                : null;
        };
    }

    /**
     * Create a provider that exchanges a web identity token (EKS, GitHub Actions) for role credentials.
     *
     * @param  array<string, mixed>  $config
     * @return \Closure(): (\LaraGram\Filesystem\Aws\Credentials|null)
     */
    public static function webIdentity(array $config = []): Closure
    {
        return function () use ($config) {
            $tokenFile = static::env('AWS_WEB_IDENTITY_TOKEN_FILE');
            $roleArn = static::env('AWS_ROLE_ARN');

            if (! $tokenFile || ! $roleArn || ! is_readable($tokenFile)) {
                return null;
            }

            $sts = new StsClient([
                'region' => $config['region'] ?? static::env('AWS_REGION') ?? static::env('AWS_DEFAULT_REGION') ?? 'us-east-1',
                'credentials' => false,
            ]);

            return $sts->createCredentials($sts->assumeRoleWithWebIdentity([
                'RoleArn' => $roleArn,
                'RoleSessionName' => static::env('AWS_ROLE_SESSION_NAME') ?? 'laragram-'.time(),
                'WebIdentityToken' => trim((string) file_get_contents($tokenFile)),
            ]));
        };
    }

    /**
     * Create a provider that reads a profile from the shared credentials or config file.
     *
     * @param  string|null  $profile
     * @param  string|null  $filename
     * @param  bool  $isConfigFile
     * @return \Closure(): (\LaraGram\Filesystem\Aws\Credentials|null)
     */
    public static function ini(?string $profile = null, ?string $filename = null, bool $isConfigFile = false): Closure
    {
        return function () use ($profile, $filename, $isConfigFile) {
            $filename ??= static::credentialsFile();
            $profile ??= static::env('AWS_PROFILE') ?? 'default';

            if (! $filename || ! is_readable($filename)) {
                return null;
            }

            $data = @parse_ini_file($filename, true, INI_SCANNER_RAW);

            if (! is_array($data)) {
                return null;
            }

            $section = $data[$profile] ?? ($isConfigFile ? ($data['profile '.$profile] ?? null) : null);

            if (empty($section['aws_access_key_id']) || empty($section['aws_secret_access_key'])) {
                return null;
            }

            return new Credentials(
                $section['aws_access_key_id'],
                $section['aws_secret_access_key'],
                $section['aws_session_token'] ?? null,
            );
        };
    }

    /**
     * Create a provider that reads the credentials of an ECS task or EKS pod identity.
     *
     * @param  float  $timeout
     * @return \Closure(): (\LaraGram\Filesystem\Aws\Credentials|null)
     */
    public static function ecs(float $timeout = 1.0): Closure
    {
        return function () use ($timeout) {
            if ($relative = static::env('AWS_CONTAINER_CREDENTIALS_RELATIVE_URI')) {
                $uri = static::ECS_ENDPOINT.$relative;
            } elseif (! $uri = static::env('AWS_CONTAINER_CREDENTIALS_FULL_URI')) {
                return null;
            }

            $token = static::env('AWS_CONTAINER_AUTHORIZATION_TOKEN');

            if ($tokenFile = static::env('AWS_CONTAINER_AUTHORIZATION_TOKEN_FILE')) {
                $token = trim((string) @file_get_contents($tokenFile));
            }

            $response = static::http($timeout)
                ->withHeaders(array_filter(['Authorization' => $token]))
                ->get($uri);

            return $response->successful() ? static::fromMetadata($response->json()) : null;
        };
    }

    /**
     * Create a provider that reads the role credentials of an EC2 instance through IMDSv2.
     *
     * @param  float  $timeout
     * @return \Closure(): (\LaraGram\Filesystem\Aws\Credentials|null)
     */
    public static function instanceProfile(float $timeout = 1.0): Closure
    {
        return function () use ($timeout) {
            if (strtolower((string) static::env('AWS_EC2_METADATA_DISABLED')) === 'true') {
                return null;
            }

            $endpoint = rtrim(static::env('AWS_EC2_METADATA_SERVICE_ENDPOINT') ?? static::IMDS_ENDPOINT, '/');

            $token = static::http($timeout)
                ->withHeaders(['X-aws-ec2-metadata-token-ttl-seconds' => '21600'])
                ->put($endpoint.'/latest/api/token');

            $headers = $token->successful() ? ['X-aws-ec2-metadata-token' => $token->body()] : [];

            $path = $endpoint.'/latest/meta-data/iam/security-credentials/';

            $role = trim(static::http($timeout)->withHeaders($headers)->get($path)->body());

            if ($role === '' || str_contains($role, '<')) {
                return null;
            }

            $response = static::http($timeout)->withHeaders($headers)->get($path.strtok($role, "\n"));

            return $response->successful() ? static::fromMetadata($response->json()) : null;
        };
    }

    /**
     * Create a provider that assumes an IAM role through STS.
     *
     * @param  \LaraGram\Filesystem\Aws\Sts\StsClient  $client
     * @param  array<string, mixed>  $parameters
     * @return \Closure(): \LaraGram\Filesystem\Aws\Credentials
     */
    public static function assumeRole(StsClient $client, array $parameters): Closure
    {
        return static::memoize(fn () => $client->createCredentials($client->assumeRole($parameters)));
    }

    /**
     * Create credentials from an ECS or EC2 metadata document.
     *
     * @param  mixed  $data
     * @return \LaraGram\Filesystem\Aws\Credentials|null
     */
    protected static function fromMetadata(mixed $data): ?Credentials
    {
        if (! is_array($data) || empty($data['AccessKeyId']) || empty($data['SecretAccessKey'])) {
            return null;
        }

        return new Credentials(
            $data['AccessKeyId'],
            $data['SecretAccessKey'],
            $data['Token'] ?? null,
            isset($data['Expiration']) ? strtotime($data['Expiration']) : null,
        );
    }

    /**
     * Create a pending HTTP request for a metadata endpoint.
     *
     * @param  float  $timeout
     * @return \LaraGram\Http\Client\PendingRequest
     */
    protected static function http(float $timeout)
    {
        $container = Container::getInstance();

        $factory = $container->bound(Factory::class) ? $container->make(Factory::class) : new Factory;

        return $factory->timeout($timeout)->connectTimeout($timeout);
    }

    /**
     * Get the path of the shared credentials file.
     *
     * @return string|null
     */
    protected static function credentialsFile(): ?string
    {
        return static::env('AWS_SHARED_CREDENTIALS_FILE') ?? static::homeFile('.aws/credentials');
    }

    /**
     * Get the path of the shared config file.
     *
     * @return string|null
     */
    protected static function configFile(): ?string
    {
        return static::env('AWS_CONFIG_FILE') ?? static::homeFile('.aws/config');
    }

    /**
     * Get the path of a file in the user's home directory.
     *
     * @param  string  $path
     * @return string|null
     */
    protected static function homeFile(string $path): ?string
    {
        $home = static::env('HOME') ?? (static::env('HOMEDRIVE') !== null ? static::env('HOMEDRIVE').static::env('HOMEPATH') : null);

        return $home ? rtrim($home, '/\\').'/'.$path : null;
    }

    /**
     * Read a non-empty environment variable.
     *
     * @param  string  $name
     * @return string|null
     */
    public static function env(string $name): ?string
    {
        foreach ([$_SERVER[$name] ?? null, $_ENV[$name] ?? null, getenv($name)] as $value) {
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }
}
