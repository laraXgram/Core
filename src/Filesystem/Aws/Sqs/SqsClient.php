<?php

namespace LaraGram\Filesystem\Aws\Sqs;

use LaraGram\Filesystem\Aws\JsonClient;

/**
 * @method \LaraGram\Filesystem\Aws\Result sendMessage(array $args)
 * @method \LaraGram\Filesystem\Aws\Result sendMessageBatch(array $args)
 * @method \LaraGram\Filesystem\Aws\Result receiveMessage(array $args)
 * @method \LaraGram\Filesystem\Aws\Result deleteMessage(array $args)
 * @method \LaraGram\Filesystem\Aws\Result deleteMessageBatch(array $args)
 * @method \LaraGram\Filesystem\Aws\Result changeMessageVisibility(array $args)
 * @method \LaraGram\Filesystem\Aws\Result getQueueAttributes(array $args)
 * @method \LaraGram\Filesystem\Aws\Result setQueueAttributes(array $args)
 * @method \LaraGram\Filesystem\Aws\Result getQueueUrl(array $args)
 * @method \LaraGram\Filesystem\Aws\Result createQueue(array $args)
 * @method \LaraGram\Filesystem\Aws\Result deleteQueue(array $args)
 * @method \LaraGram\Filesystem\Aws\Result listQueues(array $args = [])
 * @method \LaraGram\Filesystem\Aws\Result purgeQueue(array $args)
 */
class SqsClient extends JsonClient
{
    /**
     * Get the name of the service used for signing.
     *
     * @return string
     */
    protected function signingName(): string
    {
        return 'sqs';
    }

    /**
     * Get the endpoint used when none is configured.
     *
     * @return string
     */
    protected function defaultEndpoint(): string
    {
        return "https://sqs.{$this->region}.amazonaws.com";
    }

    /**
     * Get the prefix of the X-Amz-Target header.
     *
     * @return string
     */
    protected function targetPrefix(): string
    {
        return 'AmazonSQS';
    }

    /**
     * Get the exception class the client throws.
     *
     * @return class-string<\LaraGram\Filesystem\Aws\Sqs\SqsException>
     */
    protected function exceptionClass(): string
    {
        return SqsException::class;
    }
}
