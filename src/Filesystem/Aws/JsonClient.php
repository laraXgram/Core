<?php

namespace LaraGram\Filesystem\Aws;

use LaraGram\Http\Client\Core\Request;

abstract class JsonClient extends AwsClient
{
    /**
     * Get the prefix of the X-Amz-Target header, such as "DynamoDB_20120810".
     *
     * @return string
     */
    abstract protected function targetPrefix(): string;

    /**
     * Call an operation by its method name, such as "putItem".
     *
     * @param  string  $method
     * @param  array  $arguments
     * @return \LaraGram\Filesystem\Aws\Result
     */
    public function __call(string $method, array $arguments): Result
    {
        return $this->call(ucfirst($method), $arguments[0] ?? []);
    }

    /**
     * Call the given operation using the AWS JSON 1.0 protocol.
     *
     * @param  string  $operation
     * @param  array<string, mixed>  $arguments
     * @return \LaraGram\Filesystem\Aws\Result
     */
    public function call(string $operation, array $arguments = []): Result
    {
        $response = $this->sendRequest(new Request(
            'POST',
            $this->endpoint->withPath('/'),
            [
                'Content-Type' => 'application/x-amz-json-1.0',
                'X-Amz-Target' => $this->targetPrefix().'.'.$operation,
            ],
            json_encode($this->prepareArguments($arguments) ?: new \stdClass, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ));

        $data = json_decode((string) $response->getBody(), true);

        return new Result($this->prepareResult(is_array($data) ? $data : []));
    }

    /**
     * Prepare the arguments of a call before they are encoded.
     *
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    protected function prepareArguments(array $arguments): array
    {
        return $arguments;
    }

    /**
     * Prepare the decoded result of a call.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function prepareResult(array $data): array
    {
        return $data;
    }
}
