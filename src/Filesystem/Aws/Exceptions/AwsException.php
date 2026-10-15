<?php

namespace LaraGram\Filesystem\Aws\Exceptions;

use LaraGram\Http\Factory\ResponseInterface;
use RuntimeException;
use Throwable;

class AwsException extends RuntimeException
{
    /**
     * Create a new AWS exception instance.
     *
     * @param  string  $message
     * @param  string|null  $awsErrorCode
     * @param  int|null  $statusCode
     * @param  string|null  $requestId
     * @param  \LaraGram\Http\Factory\ResponseInterface|null  $response
     * @param  string|null  $awsErrorMessage
     * @param  \Throwable|null  $previous
     */
    public function __construct(
        string $message,
        protected ?string $awsErrorCode = null,
        protected ?int $statusCode = null,
        protected ?string $requestId = null,
        protected ?ResponseInterface $response = null,
        protected ?string $awsErrorMessage = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode ?? 0, $previous);
    }

    /**
     * Get the AWS error code, such as "NoSuchKey" or "ThrottlingException".
     *
     * @return string|null
     */
    public function getAwsErrorCode(): ?string
    {
        return $this->awsErrorCode;
    }

    /**
     * Get the error message returned by AWS.
     *
     * @return string|null
     */
    public function getAwsErrorMessage(): ?string
    {
        return $this->awsErrorMessage;
    }

    /**
     * Get the HTTP status code of the failed response.
     *
     * @return int|null
     */
    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    /**
     * Get the AWS request ID of the failed request.
     *
     * @return string|null
     */
    public function getAwsRequestId(): ?string
    {
        return $this->requestId;
    }

    /**
     * Get the failed HTTP response.
     *
     * @return \LaraGram\Http\Factory\ResponseInterface|null
     */
    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }
}
