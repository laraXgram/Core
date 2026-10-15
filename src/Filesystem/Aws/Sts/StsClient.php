<?php

namespace LaraGram\Filesystem\Aws\Sts;

use LaraGram\Filesystem\Aws\AwsClient;
use LaraGram\Filesystem\Aws\Credentials;
use LaraGram\Filesystem\Aws\DateTimeResult;
use LaraGram\Filesystem\Aws\Exceptions\AwsException;
use LaraGram\Filesystem\Aws\Result;
use LaraGram\Http\Client\Core\Request;
use SimpleXMLElement;

class StsClient extends AwsClient
{
    /**
     * The STS API version.
     */
    public const VERSION = '2011-06-15';

    /**
     * Get the name of the service used for signing.
     *
     * @return string
     */
    protected function signingName(): string
    {
        return 'sts';
    }

    /**
     * Get the endpoint used when none is configured.
     *
     * @return string
     */
    protected function defaultEndpoint(): string
    {
        return 'https://sts.'.$this->region.'.amazonaws.com';
    }

    /**
     * Assume the given role.
     *
     * @param  array{RoleArn: string, RoleSessionName: string, DurationSeconds?: int, ExternalId?: string, Policy?: string, SerialNumber?: string, TokenCode?: string}  $parameters
     * @return \LaraGram\Filesystem\Aws\Result
     */
    public function assumeRole(array $parameters): Result
    {
        return $this->call('AssumeRole', $parameters);
    }

    /**
     * Assume the given role with a web identity token.
     *
     * @param  array{RoleArn: string, RoleSessionName: string, WebIdentityToken: string, DurationSeconds?: int, ProviderId?: string, Policy?: string}  $parameters
     * @return \LaraGram\Filesystem\Aws\Result
     */
    public function assumeRoleWithWebIdentity(array $parameters): Result
    {
        return $this->call('AssumeRoleWithWebIdentity', $parameters);
    }

    /**
     * Get the identity of the credentials the client signs with.
     *
     * @return \LaraGram\Filesystem\Aws\Result
     */
    public function getCallerIdentity(): Result
    {
        return $this->call('GetCallerIdentity');
    }

    /**
     * Create credentials from the result of an assume role call.
     *
     * @param  \LaraGram\Filesystem\Aws\Result  $result
     * @return \LaraGram\Filesystem\Aws\Credentials
     */
    public function createCredentials(Result $result): Credentials
    {
        $credentials = $result->get('Credentials');

        if (! is_array($credentials) || empty($credentials['AccessKeyId'])) {
            throw new AwsException('STS did not return credentials.');
        }

        return new Credentials(
            $credentials['AccessKeyId'],
            $credentials['SecretAccessKey'],
            $credentials['SessionToken'] ?? null,
            isset($credentials['Expiration']) ? $credentials['Expiration']->getTimestamp() : null,
        );
    }

    /**
     * Call the given STS action using the query protocol.
     *
     * @param  string  $action
     * @param  array<string, mixed>  $parameters
     * @return \LaraGram\Filesystem\Aws\Result
     */
    protected function call(string $action, array $parameters = []): Result
    {
        $body = http_build_query(
            ['Action' => $action, 'Version' => self::VERSION, ...array_filter($parameters, fn ($value) => $value !== null && $value !== '')],
            '', '&', PHP_QUERY_RFC3986
        );

        $response = $this->sendRequest(new Request(
            'POST',
            $this->endpoint->withPath('/'),
            ['Content-Type' => 'application/x-www-form-urlencoded; charset=utf-8'],
            $body,
        ));

        $xml = simplexml_load_string((string) $response->getBody());

        $result = $xml->{$action.'Result'} ?? new SimpleXMLElement('<r/>');

        $data = [];

        if (isset($result->Credentials)) {
            $data['Credentials'] = [
                'AccessKeyId' => (string) $result->Credentials->AccessKeyId,
                'SecretAccessKey' => (string) $result->Credentials->SecretAccessKey,
                'SessionToken' => (string) $result->Credentials->SessionToken,
                'Expiration' => DateTimeResult::fromTimestamp((string) $result->Credentials->Expiration),
            ];
        }

        foreach (['AssumedRoleUser', 'SubjectFromWebIdentityToken', 'PackedPolicySize', 'Account', 'Arn', 'UserId'] as $field) {
            if (isset($result->{$field})) {
                $data[$field] = $result->{$field}->children()->count()
                    ? json_decode(json_encode($result->{$field}), true)
                    : (string) $result->{$field};
            }
        }

        return new Result($data);
    }
}
