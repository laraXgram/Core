<?php

namespace LaraGram\Filesystem\Aws\DynamoDb;

use LaraGram\Filesystem\Aws\JsonClient;

/**
 * @method \LaraGram\Filesystem\Aws\Result getItem(array $args)
 * @method \LaraGram\Filesystem\Aws\Result putItem(array $args)
 * @method \LaraGram\Filesystem\Aws\Result updateItem(array $args)
 * @method \LaraGram\Filesystem\Aws\Result deleteItem(array $args)
 * @method \LaraGram\Filesystem\Aws\Result batchGetItem(array $args)
 * @method \LaraGram\Filesystem\Aws\Result batchWriteItem(array $args)
 * @method \LaraGram\Filesystem\Aws\Result query(array $args)
 * @method \LaraGram\Filesystem\Aws\Result scan(array $args)
 * @method \LaraGram\Filesystem\Aws\Result createTable(array $args)
 * @method \LaraGram\Filesystem\Aws\Result deleteTable(array $args)
 * @method \LaraGram\Filesystem\Aws\Result describeTable(array $args)
 * @method \LaraGram\Filesystem\Aws\Result updateTimeToLive(array $args)
 * @method \LaraGram\Filesystem\Aws\Result transactWriteItems(array $args)
 * @method \LaraGram\Filesystem\Aws\Result transactGetItems(array $args)
 */
class DynamoDbClient extends JsonClient
{
    /**
     * Get the name of the service used for signing.
     *
     * @return string
     */
    protected function signingName(): string
    {
        return 'dynamodb';
    }

    /**
     * Get the endpoint used when none is configured.
     *
     * @return string
     */
    protected function defaultEndpoint(): string
    {
        return "https://dynamodb.{$this->region}.amazonaws.com";
    }

    /**
     * Get the prefix of the X-Amz-Target header.
     *
     * @return string
     */
    protected function targetPrefix(): string
    {
        return 'DynamoDB_20120810';
    }

    /**
     * Get the exception class the client throws.
     *
     * @return class-string<\LaraGram\Filesystem\Aws\DynamoDb\DynamoDbException>
     */
    protected function exceptionClass(): string
    {
        return DynamoDbException::class;
    }

    /**
     * Base64 encode the binary attribute values.
     *
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    protected function prepareArguments(array $arguments): array
    {
        return $this->mapBinary($arguments, base64_encode(...));
    }

    /**
     * Base64 decode the binary attribute values.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function prepareResult(array $data): array
    {
        return $this->mapBinary($data, fn (string $value) => (string) base64_decode($value, true));
    }

    /**
     * Apply the callback to every binary ("B" and "BS") attribute value.
     *
     * @param  array<array-key, mixed>  $data
     * @param  callable(string): string  $callback
     * @return array<array-key, mixed>
     */
    protected function mapBinary(array $data, callable $callback): array
    {
        if (count($data) === 1 && isset($data['B']) && is_string($data['B'])) {
            return ['B' => $callback($data['B'])];
        }

        if (count($data) === 1 && isset($data['BS']) && is_array($data['BS'])) {
            return ['BS' => array_map(fn ($value) => $callback((string) $value), $data['BS'])];
        }

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->mapBinary($value, $callback);
            }
        }

        return $data;
    }
}
