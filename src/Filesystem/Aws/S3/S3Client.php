<?php

namespace LaraGram\Filesystem\Aws\S3;

use BadMethodCallException;
use DateTimeInterface;
use Generator;
use InvalidArgumentException;
use LaraGram\Filesystem\Aws\AwsClient;
use LaraGram\Filesystem\Aws\Command;
use LaraGram\Filesystem\Aws\DateTimeResult;
use LaraGram\Filesystem\Aws\Result;
use LaraGram\Filesystem\Aws\SignatureV4;
use LaraGram\Http\Client\Core\PsrUtils;
use LaraGram\Http\Client\Core\Request;
use LaraGram\Http\Factory\RequestInterface;
use LaraGram\Http\Factory\ResponseInterface;
use LaraGram\Http\Factory\StreamInterface;
use LaraGram\Http\Factory\UriInterface;
use SimpleXMLElement;
use Throwable;

/**
 * @method \LaraGram\Filesystem\Aws\Result headObject(array $args)
 * @method \LaraGram\Filesystem\Aws\Result getObject(array $args)
 * @method \LaraGram\Filesystem\Aws\Result putObject(array $args)
 * @method \LaraGram\Filesystem\Aws\Result deleteObject(array $args)
 * @method \LaraGram\Filesystem\Aws\Result deleteObjects(array $args)
 * @method \LaraGram\Filesystem\Aws\Result copyObject(array $args)
 * @method \LaraGram\Filesystem\Aws\Result getObjectAcl(array $args)
 * @method \LaraGram\Filesystem\Aws\Result putObjectAcl(array $args)
 * @method \LaraGram\Filesystem\Aws\Result listObjects(array $args)
 * @method \LaraGram\Filesystem\Aws\Result listObjectsV2(array $args)
 * @method \LaraGram\Filesystem\Aws\Result listBuckets(array $args = [])
 * @method \LaraGram\Filesystem\Aws\Result headBucket(array $args)
 * @method \LaraGram\Filesystem\Aws\Result createBucket(array $args)
 * @method \LaraGram\Filesystem\Aws\Result deleteBucket(array $args)
 * @method \LaraGram\Filesystem\Aws\Result createMultipartUpload(array $args)
 * @method \LaraGram\Filesystem\Aws\Result uploadPart(array $args)
 * @method \LaraGram\Filesystem\Aws\Result uploadPartCopy(array $args)
 * @method \LaraGram\Filesystem\Aws\Result completeMultipartUpload(array $args)
 * @method \LaraGram\Filesystem\Aws\Result abortMultipartUpload(array $args)
 */
class S3Client extends AwsClient
{
    /**
     * The smallest part S3 accepts in a multipart upload, except the last one.
     */
    public const PART_MIN_SIZE = 5242880;

    /**
     * The largest object a single PUT or copy may create.
     */
    public const PART_MAX_SIZE = 5368709120;

    /**
     * The largest number of parts in a multipart upload.
     */
    public const PART_MAX_NUM = 10000;

    /**
     * The size above which uploads are split into parts by default.
     */
    public const DEFAULT_UPLOAD_THRESHOLD = 16777216;

    /**
     * The HTTP method and URI template of each supported operation.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    protected const OPERATIONS = [
        'ListBuckets' => ['GET', '/'],
        'HeadBucket' => ['HEAD', '/{Bucket}'],
        'CreateBucket' => ['PUT', '/{Bucket}'],
        'DeleteBucket' => ['DELETE', '/{Bucket}'],
        'ListObjects' => ['GET', '/{Bucket}'],
        'ListObjectsV2' => ['GET', '/{Bucket}?list-type=2'],
        'DeleteObjects' => ['POST', '/{Bucket}?delete'],
        'HeadObject' => ['HEAD', '/{Bucket}/{Key}'],
        'GetObject' => ['GET', '/{Bucket}/{Key}'],
        'PutObject' => ['PUT', '/{Bucket}/{Key}'],
        'CopyObject' => ['PUT', '/{Bucket}/{Key}'],
        'DeleteObject' => ['DELETE', '/{Bucket}/{Key}'],
        'GetObjectAcl' => ['GET', '/{Bucket}/{Key}?acl'],
        'PutObjectAcl' => ['PUT', '/{Bucket}/{Key}?acl'],
        'CreateMultipartUpload' => ['POST', '/{Bucket}/{Key}?uploads'],
        'UploadPart' => ['PUT', '/{Bucket}/{Key}'],
        'UploadPartCopy' => ['PUT', '/{Bucket}/{Key}'],
        'CompleteMultipartUpload' => ['POST', '/{Bucket}/{Key}'],
        'AbortMultipartUpload' => ['DELETE', '/{Bucket}/{Key}'],
    ];

    /**
     * The request headers of the operation parameters.
     *
     * @var array<string, string>
     */
    protected const HEADERS = [
        'ACL' => 'x-amz-acl',
        'BucketKeyEnabled' => 'x-amz-server-side-encryption-bucket-key-enabled',
        'BypassGovernanceRetention' => 'x-amz-bypass-governance-retention',
        'CacheControl' => 'Cache-Control',
        'ChecksumAlgorithm' => 'x-amz-sdk-checksum-algorithm',
        'ChecksumCRC32' => 'x-amz-checksum-crc32',
        'ChecksumCRC32C' => 'x-amz-checksum-crc32c',
        'ChecksumMode' => 'x-amz-checksum-mode',
        'ChecksumSHA1' => 'x-amz-checksum-sha1',
        'ChecksumSHA256' => 'x-amz-checksum-sha256',
        'ContentDisposition' => 'Content-Disposition',
        'ContentEncoding' => 'Content-Encoding',
        'ContentLanguage' => 'Content-Language',
        'ContentLength' => 'Content-Length',
        'ContentMD5' => 'Content-MD5',
        'ContentType' => 'Content-Type',
        'CopySource' => 'x-amz-copy-source',
        'CopySourceIfMatch' => 'x-amz-copy-source-if-match',
        'CopySourceIfModifiedSince' => 'x-amz-copy-source-if-modified-since',
        'CopySourceIfNoneMatch' => 'x-amz-copy-source-if-none-match',
        'CopySourceIfUnmodifiedSince' => 'x-amz-copy-source-if-unmodified-since',
        'CopySourceRange' => 'x-amz-copy-source-range',
        'CopySourceSSECustomerAlgorithm' => 'x-amz-copy-source-server-side-encryption-customer-algorithm',
        'CopySourceSSECustomerKey' => 'x-amz-copy-source-server-side-encryption-customer-key',
        'CopySourceSSECustomerKeyMD5' => 'x-amz-copy-source-server-side-encryption-customer-key-MD5',
        'ExpectedBucketOwner' => 'x-amz-expected-bucket-owner',
        'Expires' => 'Expires',
        'GrantFullControl' => 'x-amz-grant-full-control',
        'GrantRead' => 'x-amz-grant-read',
        'GrantReadACP' => 'x-amz-grant-read-acp',
        'GrantWrite' => 'x-amz-grant-write',
        'GrantWriteACP' => 'x-amz-grant-write-acp',
        'IfMatch' => 'If-Match',
        'IfModifiedSince' => 'If-Modified-Since',
        'IfNoneMatch' => 'If-None-Match',
        'IfUnmodifiedSince' => 'If-Unmodified-Since',
        'MetadataDirective' => 'x-amz-metadata-directive',
        'MFA' => 'x-amz-mfa',
        'ObjectLockLegalHoldStatus' => 'x-amz-object-lock-legal-hold',
        'ObjectLockMode' => 'x-amz-object-lock-mode',
        'ObjectLockRetainUntilDate' => 'x-amz-object-lock-retain-until-date',
        'Range' => 'Range',
        'RequestPayer' => 'x-amz-request-payer',
        'ServerSideEncryption' => 'x-amz-server-side-encryption',
        'SSECustomerAlgorithm' => 'x-amz-server-side-encryption-customer-algorithm',
        'SSECustomerKey' => 'x-amz-server-side-encryption-customer-key',
        'SSECustomerKeyMD5' => 'x-amz-server-side-encryption-customer-key-MD5',
        'SSEKMSEncryptionContext' => 'x-amz-server-side-encryption-context',
        'SSEKMSKeyId' => 'x-amz-server-side-encryption-aws-kms-key-id',
        'StorageClass' => 'x-amz-storage-class',
        'Tagging' => 'x-amz-tagging',
        'TaggingDirective' => 'x-amz-tagging-directive',
        'WebsiteRedirectLocation' => 'x-amz-website-redirect-location',
    ];

    /**
     * The query string parameters of the operation parameters.
     *
     * @var array<string, string>
     */
    protected const QUERY = [
        'ContinuationToken' => 'continuation-token',
        'Delimiter' => 'delimiter',
        'EncodingType' => 'encoding-type',
        'FetchOwner' => 'fetch-owner',
        'Marker' => 'marker',
        'MaxKeys' => 'max-keys',
        'PartNumber' => 'partNumber',
        'Prefix' => 'prefix',
        'ResponseCacheControl' => 'response-cache-control',
        'ResponseContentDisposition' => 'response-content-disposition',
        'ResponseContentEncoding' => 'response-content-encoding',
        'ResponseContentLanguage' => 'response-content-language',
        'ResponseContentType' => 'response-content-type',
        'ResponseExpires' => 'response-expires',
        'StartAfter' => 'start-after',
        'UploadId' => 'uploadId',
        'VersionId' => 'versionId',
    ];

    /**
     * The result fields read from the response headers.
     *
     * @var array<string, string>
     */
    protected const RESPONSE_HEADERS = [
        'AcceptRanges' => 'accept-ranges',
        'BucketKeyEnabled' => 'x-amz-server-side-encryption-bucket-key-enabled',
        'BucketRegion' => 'x-amz-bucket-region',
        'CacheControl' => 'cache-control',
        'ChecksumCRC32' => 'x-amz-checksum-crc32',
        'ChecksumCRC32C' => 'x-amz-checksum-crc32c',
        'ChecksumSHA1' => 'x-amz-checksum-sha1',
        'ChecksumSHA256' => 'x-amz-checksum-sha256',
        'ContentDisposition' => 'content-disposition',
        'ContentEncoding' => 'content-encoding',
        'ContentLanguage' => 'content-language',
        'ContentLength' => 'content-length',
        'ContentRange' => 'content-range',
        'ContentType' => 'content-type',
        'DeleteMarker' => 'x-amz-delete-marker',
        'ETag' => 'etag',
        'Expiration' => 'x-amz-expiration',
        'Expires' => 'expires',
        'LastModified' => 'last-modified',
        'Location' => 'location',
        'ObjectLockLegalHoldStatus' => 'x-amz-object-lock-legal-hold',
        'ObjectLockMode' => 'x-amz-object-lock-mode',
        'ObjectLockRetainUntilDate' => 'x-amz-object-lock-retain-until-date',
        'PartsCount' => 'x-amz-mp-parts-count',
        'ReplicationStatus' => 'x-amz-replication-status',
        'RequestCharged' => 'x-amz-request-charged',
        'Restore' => 'x-amz-restore',
        'ServerSideEncryption' => 'x-amz-server-side-encryption',
        'SSECustomerAlgorithm' => 'x-amz-server-side-encryption-customer-algorithm',
        'SSECustomerKeyMD5' => 'x-amz-server-side-encryption-customer-key-md5',
        'SSEKMSKeyId' => 'x-amz-server-side-encryption-aws-kms-key-id',
        'StorageClass' => 'x-amz-storage-class',
        'TagCount' => 'x-amz-tagging-count',
        'VersionId' => 'x-amz-version-id',
        'WebsiteRedirectLocation' => 'x-amz-website-redirect-location',
    ];

    /**
     * The XML elements that always hold a list.
     *
     * @var string[]
     */
    protected const XML_LISTS = ['Contents', 'CommonPrefixes', 'Deleted', 'Error', 'Grant', 'Part', 'Bucket', 'Upload', 'Version', 'DeleteMarker'];

    /**
     * Get the name of the service used for signing.
     *
     * @return string
     */
    protected function signingName(): string
    {
        return 's3';
    }

    /**
     * Get the endpoint used when none is configured.
     *
     * @return string
     */
    protected function defaultEndpoint(): string
    {
        return match (true) {
            (bool) ($this->config['use_accelerate_endpoint'] ?? false) => 'https://s3-accelerate.amazonaws.com',
            (bool) ($this->config['use_dual_stack_endpoint'] ?? false) => "https://s3.dualstack.{$this->region}.amazonaws.com",
            default => "https://s3.{$this->region}.amazonaws.com",
        };
    }

    /**
     * Determine if the S3 signing rules apply to this client.
     *
     * @return bool
     */
    protected function usesS3Signing(): bool
    {
        return true;
    }

    /**
     * Get the exception class the client throws.
     *
     * @return class-string<\LaraGram\Filesystem\Aws\S3\S3Exception>
     */
    protected function exceptionClass(): string
    {
        return S3Exception::class;
    }

    /**
     * Call an operation by its method name, such as "putObject".
     *
     * @param  string  $method
     * @param  array  $arguments
     * @return \LaraGram\Filesystem\Aws\Result
     */
    public function __call(string $method, array $arguments): Result
    {
        $name = ucfirst($method);

        if (! isset(static::OPERATIONS[$name])) {
            throw new BadMethodCallException("The S3 operation [{$name}] is not supported.");
        }

        return $this->execute($this->getCommand($name, $arguments[0] ?? []));
    }

    /**
     * Create a command for the given operation.
     *
     * @param  string  $name
     * @param  array<string, mixed>  $args
     * @return \LaraGram\Filesystem\Aws\Command
     */
    public function getCommand(string $name, array $args = []): Command
    {
        if (! isset(static::OPERATIONS[$name])) {
            throw new InvalidArgumentException("The S3 operation [{$name}] is not supported.");
        }

        return new Command($name, $args);
    }

    /**
     * Execute the given command.
     *
     * @param  \LaraGram\Filesystem\Aws\Command  $command
     * @return \LaraGram\Filesystem\Aws\Result
     *
     * @throws \LaraGram\Filesystem\Aws\S3\S3Exception
     */
    public function execute(Command $command): Result
    {
        $args = $command->toArray();

        $request = $this->buildRequest($command->getName(), $args);

        $response = $this->sendRequest($request, (bool) ($args['@http']['stream'] ?? false));

        return $this->parseResponse($command->getName(), $args, $request, $response);
    }

    /**
     * Get a generator that yields every page of a listing operation.
     *
     * @param  string  $name
     * @param  array<string, mixed>  $args
     * @return \Generator<int, \LaraGram\Filesystem\Aws\Result>
     */
    public function getPaginator(string $name, array $args = []): Generator
    {
        [$input, $output] = match ($name) {
            'ListObjectsV2' => ['ContinuationToken', 'NextContinuationToken'],
            'ListObjects' => ['Marker', 'NextMarker'],
            default => throw new InvalidArgumentException("The S3 operation [{$name}] cannot be paginated."),
        };

        do {
            $result = $this->execute($this->getCommand($name, $args));

            yield $result;

            $token = $result->get($output);

            if ($name === 'ListObjects' && ! $token && $result->get('IsTruncated')) {
                $contents = $result->get('Contents') ?? [];
                $token = end($contents)['Key'] ?? null;
            }

            $args[$input] = $token;
        } while ($result->get('IsTruncated') && $token);
    }

    /**
     * Get a generator that yields every object of a listing operation.
     *
     * @param  string  $name
     * @param  array<string, mixed>  $args
     * @return \Generator<int, array<string, mixed>>
     */
    public function getIterator(string $name, array $args = []): Generator
    {
        foreach ($this->getPaginator($name, $args) as $result) {
            yield from $result->get('Contents') ?? [];
        }
    }

    /**
     * Create a presigned request for the given command.
     *
     * @param  \LaraGram\Filesystem\Aws\Command  $command
     * @param  \DateTimeInterface|int|string  $expires
     * @param  array{start_time?: \DateTimeInterface|int|string}  $options
     * @return \LaraGram\Http\Factory\RequestInterface
     */
    public function createPresignedRequest(Command $command, DateTimeInterface|int|string $expires, array $options = []): RequestInterface
    {
        $request = $this->buildRequest($command->getName(), $command->toArray(), withBody: false);

        foreach ((array) ($this->config['headers'] ?? []) as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if (! $credentials = $this->getCredentials()) {
            return $request;
        }

        return $this->signer->presign($request, $credentials, $expires, $options);
    }

    /**
     * Get the plain URL of the given object.
     *
     * @param  string  $bucket
     * @param  string  $key
     * @return string
     */
    public function getObjectUrl(string $bucket, string $key): string
    {
        return (string) $this->objectUri($bucket, $key);
    }

    /**
     * Determine if the given object exists.
     *
     * @param  string  $bucket
     * @param  string  $key
     * @param  bool  $includeDeleteMarkers
     * @param  array<string, mixed>  $options
     * @return bool
     */
    public function doesObjectExistV2(string $bucket, string $key, bool $includeDeleteMarkers = false, array $options = []): bool
    {
        try {
            $this->execute($this->getCommand('HeadObject', ['Bucket' => $bucket, 'Key' => $key] + $options));

            return true;
        } catch (S3Exception $e) {
            if ($includeDeleteMarkers && $e->getResponse()?->getHeaderLine('x-amz-delete-marker') === 'true') {
                return true;
            }

            if ($e->getStatusCode() === null || $e->getStatusCode() >= 500) {
                throw $e;
            }

            return false;
        }
    }

    /**
     * Determine if the given object exists.
     *
     * @param  string  $bucket
     * @param  string  $key
     * @param  array<string, mixed>  $options
     * @return bool
     */
    public function doesObjectExist(string $bucket, string $key, array $options = []): bool
    {
        return $this->doesObjectExistV2($bucket, $key, false, $options);
    }

    /**
     * Determine if the given bucket exists.
     *
     * @param  string  $bucket
     * @param  bool  $accept403
     * @return bool
     */
    public function doesBucketExistV2(string $bucket, bool $accept403 = false): bool
    {
        try {
            $this->execute($this->getCommand('HeadBucket', ['Bucket' => $bucket]));

            return true;
        } catch (S3Exception $e) {
            if ($accept403 && $e->getStatusCode() === 403) {
                return true;
            }

            if ($e->getStatusCode() === null || $e->getStatusCode() >= 500) {
                throw $e;
            }

            return false;
        }
    }

    /**
     * Upload the given body, in parts when it is larger than the multipart threshold.
     *
     * @param  string  $bucket
     * @param  string  $key
     * @param  string|resource|\LaraGram\Http\Factory\StreamInterface  $body
     * @param  string  $acl
     * @param  array{params?: array, mup_threshold?: int, part_size?: int, before_upload?: callable, add_content_md5?: bool, concurrency?: int}  $options
     * @return \LaraGram\Filesystem\Aws\Result
     */
    public function upload(string $bucket, string $key, $body, string $acl = 'private', array $options = []): Result
    {
        $stream = PsrUtils::streamFor($body);
        $params = ['Bucket' => $bucket, 'Key' => $key, 'ACL' => $acl, ...($options['params'] ?? [])];
        $size = $stream->getSize();

        if ($size !== null && $size < ($options['mup_threshold'] ?? static::DEFAULT_UPLOAD_THRESHOLD)) {
            if (! empty($options['add_content_md5']) && $stream->isSeekable()) {
                $params['ContentMD5'] = base64_encode(PsrUtils::hash($stream, 'md5', true));
                $stream->rewind();
            }

            $command = $this->getCommand('PutObject', [...$params, 'Body' => $stream]);

            if (isset($options['before_upload'])) {
                $options['before_upload']($command);
            }

            $result = $this->execute($command);
        } else {
            $result = $this->multipartUpload($stream, $size, $params, $options);
        }

        $result['ObjectURL'] ??= $this->getObjectUrl($bucket, $key);

        return $result;
    }

    /**
     * Upload the given stream in parts.
     *
     * @param  \LaraGram\Http\Factory\StreamInterface  $stream
     * @param  int|null  $size
     * @param  array<string, mixed>  $params
     * @param  array<string, mixed>  $options
     * @return \LaraGram\Filesystem\Aws\Result
     */
    protected function multipartUpload(StreamInterface $stream, ?int $size, array $params, array $options): Result
    {
        $partSize = max(
            (int) ($options['part_size'] ?? static::PART_MIN_SIZE),
            static::PART_MIN_SIZE,
            $size ? (int) ceil($size / static::PART_MAX_NUM) : 0,
        );

        unset($params['ChecksumAlgorithm'], $params['ContentLength'], $params['ContentMD5']);

        $uploadId = $this->createMultipartUpload($params)->get('UploadId');

        $location = ['Bucket' => $params['Bucket'], 'Key' => $params['Key']];
        $parts = [];

        try {
            for ($number = 1; ! $stream->eof(); $number++) {
                $chunk = '';

                while (strlen($chunk) < $partSize && ! $stream->eof()) {
                    $chunk .= $stream->read($partSize - strlen($chunk));
                }

                if ($chunk === '' && $number > 1) {
                    break;
                }

                $partParams = [...$location, 'UploadId' => $uploadId, 'PartNumber' => $number, 'Body' => $chunk];

                foreach (['SSECustomerAlgorithm', 'SSECustomerKey', 'SSECustomerKeyMD5', 'RequestPayer', 'ExpectedBucketOwner'] as $name) {
                    if (isset($params[$name])) {
                        $partParams[$name] = $params[$name];
                    }
                }

                if (! empty($options['add_content_md5'])) {
                    $partParams['ContentMD5'] = base64_encode(md5($chunk, true));
                }

                $command = $this->getCommand('UploadPart', $partParams);

                if (isset($options['before_upload'])) {
                    $options['before_upload']($command);
                }

                $parts[] = ['PartNumber' => $number, 'ETag' => $this->execute($command)->get('ETag')];
            }

            return $this->completeMultipartUpload([
                ...$location,
                'UploadId' => $uploadId,
                'MultipartUpload' => ['Parts' => $parts],
                ...array_intersect_key($params, array_flip(['RequestPayer', 'ExpectedBucketOwner', 'SSECustomerAlgorithm', 'SSECustomerKey', 'SSECustomerKeyMD5'])),
            ]);
        } catch (Throwable $e) {
            try {
                $this->abortMultipartUpload([...$location, 'UploadId' => $uploadId]);
            } catch (Throwable) {
                //
            }

            throw $e;
        }
    }

    /**
     * Copy an object, in parts when it is larger than a single copy allows.
     *
     * @param  string  $fromBucket
     * @param  string  $fromKey
     * @param  string  $destBucket
     * @param  string  $destKey
     * @param  string  $acl
     * @param  array{params?: array, mup_threshold?: int, part_size?: int, before_upload?: callable, version_id?: string, MetadataDirective?: string}  $options
     * @return \LaraGram\Filesystem\Aws\Result
     */
    public function copy(string $fromBucket, string $fromKey, string $destBucket, string $destKey, string $acl = 'private', array $options = []): Result
    {
        $source = '/'.$fromBucket.'/'.$this->encodeKey($fromKey)
            .(isset($options['version_id']) ? '?versionId='.rawurlencode($options['version_id']) : '');

        $params = [
            'Bucket' => $destBucket,
            'Key' => $destKey,
            'ACL' => $acl,
            'CopySource' => $source,
            ...(isset($options['MetadataDirective']) ? ['MetadataDirective' => $options['MetadataDirective']] : []),
            ...($options['params'] ?? []),
        ];

        $head = $this->headObject(array_filter([
            'Bucket' => $fromBucket,
            'Key' => $fromKey,
            'VersionId' => $options['version_id'] ?? null,
            'SSECustomerAlgorithm' => $params['CopySourceSSECustomerAlgorithm'] ?? null,
            'SSECustomerKey' => $params['CopySourceSSECustomerKey'] ?? null,
        ]));

        $size = (int) $head->get('ContentLength');

        if ($size <= ($options['mup_threshold'] ?? static::PART_MAX_SIZE)) {
            unset($params['ContentType'], $params['ContentLength'], $params['ContentMD5']);

            $command = $this->getCommand('CopyObject', $params);

            if (isset($options['before_upload'])) {
                $options['before_upload']($command);
            }

            return $this->execute($command);
        }

        return $this->multipartCopy($size, $params, $head, $options);
    }

    /**
     * Copy an object in parts.
     *
     * @param  int  $size
     * @param  array<string, mixed>  $params
     * @param  \LaraGram\Filesystem\Aws\Result  $head
     * @param  array<string, mixed>  $options
     * @return \LaraGram\Filesystem\Aws\Result
     */
    protected function multipartCopy(int $size, array $params, Result $head, array $options): Result
    {
        $partSize = max((int) ($options['part_size'] ?? 0), (int) ceil($size / static::PART_MAX_NUM), 104857600);

        $source = $params['CopySource'];

        $create = array_diff_key($params, array_flip([
            'CopySource', 'MetadataDirective', 'TaggingDirective', 'CopySourceSSECustomerAlgorithm',
            'CopySourceSSECustomerKey', 'CopySourceSSECustomerKeyMD5', 'ContentLength', 'ContentMD5',
        ]));

        if (($params['MetadataDirective'] ?? 'COPY') === 'COPY') {
            $create['ContentType'] ??= $head->get('ContentType');
            $create['Metadata'] ??= $head->get('Metadata');
        }

        $uploadId = $this->createMultipartUpload(array_filter($create))->get('UploadId');

        $location = ['Bucket' => $params['Bucket'], 'Key' => $params['Key']];
        $parts = [];

        try {
            for ($offset = 0, $number = 1; $offset < $size; $offset += $partSize, $number++) {
                $command = $this->getCommand('UploadPartCopy', array_filter([
                    ...$location,
                    'UploadId' => $uploadId,
                    'PartNumber' => $number,
                    'CopySource' => $source,
                    'CopySourceRange' => 'bytes='.$offset.'-'.(min($offset + $partSize, $size) - 1),
                    'CopySourceSSECustomerAlgorithm' => $params['CopySourceSSECustomerAlgorithm'] ?? null,
                    'CopySourceSSECustomerKey' => $params['CopySourceSSECustomerKey'] ?? null,
                    'SSECustomerAlgorithm' => $params['SSECustomerAlgorithm'] ?? null,
                    'SSECustomerKey' => $params['SSECustomerKey'] ?? null,
                ]));

                if (isset($options['before_upload'])) {
                    $options['before_upload']($command);
                }

                $parts[] = ['PartNumber' => $number, 'ETag' => $this->execute($command)->get('CopyPartResult')['ETag'] ?? null];
            }

            return $this->completeMultipartUpload([...$location, 'UploadId' => $uploadId, 'MultipartUpload' => ['Parts' => $parts]]);
        } catch (Throwable $e) {
            try {
                $this->abortMultipartUpload([...$location, 'UploadId' => $uploadId]);
            } catch (Throwable) {
                //
            }

            throw $e;
        }
    }

    /**
     * Delete every object whose key starts with the prefix and matches the pattern.
     *
     * @param  string  $bucket
     * @param  string  $prefix
     * @param  string  $regex
     * @param  array<string, mixed>  $options
     * @return void
     */
    public function deleteMatchingObjects(string $bucket, string $prefix = '', string $regex = '', array $options = []): void
    {
        $batch = [];

        $flush = function () use (&$batch, $bucket) {
            if ($batch === []) {
                return;
            }

            $result = $this->deleteObjects(['Bucket' => $bucket, 'Delete' => ['Objects' => $batch, 'Quiet' => true]]);

            $batch = [];

            if ($errors = $result->get('Errors')) {
                throw new S3Exception(sprintf(
                    'Unable to delete [%s]: %s', $errors[0]['Key'] ?? '', $errors[0]['Message'] ?? $errors[0]['Code'] ?? ''
                ), $errors[0]['Code'] ?? null);
            }
        };

        foreach ($this->getIterator('ListObjectsV2', ['Bucket' => $bucket, 'Prefix' => $prefix] + $options) as $object) {
            if ($regex !== '' && ! preg_match($regex, $object['Key'])) {
                continue;
            }

            $batch[] = ['Key' => $object['Key']];

            if (count($batch) === 1000) {
                $flush();
            }
        }

        $flush();
    }

    /**
     * Build the HTTP request of the given operation.
     *
     * @param  string  $name
     * @param  array<string, mixed>  $args
     * @param  bool  $withBody
     * @return \LaraGram\Http\Factory\RequestInterface
     */
    protected function buildRequest(string $name, array $args, bool $withBody = true): RequestInterface
    {
        [$method, $template] = static::OPERATIONS[$name];

        [$path, $operationQuery] = array_pad(explode('?', $template, 2), 2, '');

        $uri = str_contains($path, '{Key}')
            ? $this->objectUri($this->requireArg($args, 'Bucket'), $this->requireArg($args, 'Key'))
            : (str_contains($path, '{Bucket}') ? $this->objectUri($this->requireArg($args, 'Bucket')) : $this->endpoint->withPath('/'));

        $query = $operationQuery === '' ? [] : [$operationQuery];

        foreach (static::QUERY as $param => $parameter) {
            if (isset($args[$param]) && $args[$param] !== '') {
                $query[] = rawurlencode($parameter).'='.rawurlencode($this->stringify($args[$param]));
            }
        }

        $uri = $uri->withQuery(implode('&', $query));

        [$headers, $body] = [$this->buildHeaders($args), null];

        if ($withBody) {
            $body = match ($name) {
                'PutObject', 'UploadPart' => isset($args['Body']) ? PsrUtils::streamFor($args['Body']) : null,
                'DeleteObjects' => $this->deleteObjectsXml($args['Delete'] ?? []),
                'CompleteMultipartUpload' => $this->completeMultipartXml($args['MultipartUpload']['Parts'] ?? []),
                'CreateBucket' => $this->createBucketXml($args),
                default => null,
            };

            if (is_string($body) && $body !== '') {
                $headers['Content-Type'] = 'application/xml';
                $headers['Content-MD5'] ??= base64_encode(md5($body, true));
            }

            if ($body instanceof StreamInterface) {
                $headers = $this->addChecksum($headers, $args, $body);
            }
        }

        return new Request($method, $uri, $headers, $body);
    }

    /**
     * Build the request headers of the given parameters.
     *
     * @param  array<string, mixed>  $args
     * @return array<string, string>
     */
    protected function buildHeaders(array $args): array
    {
        foreach (['SSECustomerKey', 'CopySourceSSECustomerKey'] as $param) {
            if (isset($args[$param])) {
                $args[$param.'MD5'] ??= base64_encode(md5($args[$param], true));
                $args[$param] = base64_encode($args[$param]);
            }
        }

        $headers = [];

        foreach (static::HEADERS as $param => $header) {
            if (! isset($args[$param]) || $args[$param] === '') {
                continue;
            }

            $value = $args[$param];

            $headers[$header] = match (true) {
                $param === 'ObjectLockRetainUntilDate' && ! is_string($value) => $this->toDateTime($value)->format('Y-m-d\TH:i:s\Z'),
                in_array($param, ['Expires', 'IfModifiedSince', 'IfUnmodifiedSince', 'CopySourceIfModifiedSince', 'CopySourceIfUnmodifiedSince']) && ! is_string($value) => gmdate('D, d M Y H:i:s \G\M\T', $this->toDateTime($value)->getTimestamp()),
                default => $this->stringify($value),
            };
        }

        foreach ((array) ($args['Metadata'] ?? []) as $name => $value) {
            $headers['x-amz-meta-'.$name] = (string) $value;
        }

        return $headers;
    }

    /**
     * Add the requested checksum header for the given body.
     *
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $args
     * @param  \LaraGram\Http\Factory\StreamInterface  $body
     * @return array<string, string>
     */
    protected function addChecksum(array $headers, array $args, StreamInterface $body): array
    {
        $algorithm = strtoupper((string) ($args['ChecksumAlgorithm'] ?? ''));

        if ($algorithm === '' || isset($headers['x-amz-checksum-'.strtolower($algorithm)]) || ! $body->isSeekable()) {
            return $headers;
        }

        $raw = match ($algorithm) {
            'CRC32' => PsrUtils::hash($body, 'crc32b', true),
            'CRC32C' => PsrUtils::hash($body, 'crc32c', true),
            'SHA1' => PsrUtils::hash($body, 'sha1', true),
            'SHA256' => PsrUtils::hash($body, 'sha256', true),
            default => throw new InvalidArgumentException("The checksum algorithm [{$algorithm}] is not supported."),
        };

        $body->rewind();

        $headers['x-amz-checksum-'.strtolower($algorithm)] = base64_encode($raw);

        return $headers;
    }

    /**
     * Parse the response of the given operation.
     *
     * @param  string  $name
     * @param  array<string, mixed>  $args
     * @param  \LaraGram\Http\Factory\RequestInterface  $request
     * @param  \LaraGram\Http\Factory\ResponseInterface  $response
     * @return \LaraGram\Filesystem\Aws\Result
     */
    protected function parseResponse(string $name, array $args, RequestInterface $request, ResponseInterface $response): Result
    {
        $data = $this->parseHeaders($response);

        if ($name === 'GetObject') {
            $data['Body'] = $response->getBody();
        } elseif (in_array($name, ['PutObject', 'HeadObject', 'DeleteObject', 'UploadPart', 'AbortMultipartUpload', 'PutObjectAcl', 'HeadBucket', 'DeleteBucket', 'CreateBucket'])) {
            if ($name === 'PutObject') {
                $data['ObjectURL'] = (string) $request->getUri()->withQuery('');
            }
        } else {
            $body = (string) $response->getBody();

            $xml = $body === '' ? null : @simplexml_load_string($body);

            if ($xml instanceof SimpleXMLElement) {
                if ($xml->getName() === 'Error') {
                    throw $this->createException($request, $response->withStatus(500));
                }

                $parsed = $this->xmlToArray($xml);

                $data += match ($name) {
                    'CopyObject' => ['CopyObjectResult' => $parsed],
                    'UploadPartCopy' => ['CopyPartResult' => $parsed],
                    'GetObjectAcl' => $this->parseAcl($xml),
                    'ListBuckets' => ['Buckets' => $parsed['Buckets']['Bucket'] ?? [], 'Owner' => $parsed['Owner'] ?? null],
                    'DeleteObjects' => ['Deleted' => $parsed['Deleted'] ?? [], 'Errors' => $parsed['Error'] ?? []],
                    default => $parsed,
                };
            }

            if (($args['EncodingType'] ?? null) === 'url') {
                $data = $this->decodeListing($data);
            }
        }

        $data['@metadata'] = [
            'statusCode' => $response->getStatusCode(),
            'effectiveUri' => (string) $request->getUri(),
            'headers' => array_change_key_case(array_map(fn ($values) => implode(', ', $values), $response->getHeaders())),
        ];

        return new Result($data);
    }

    /**
     * Read the result fields of the response headers.
     *
     * @param  \LaraGram\Http\Factory\ResponseInterface  $response
     * @return array<string, mixed>
     */
    protected function parseHeaders(ResponseInterface $response): array
    {
        $data = [];

        foreach (static::RESPONSE_HEADERS as $field => $header) {
            if (! $response->hasHeader($header)) {
                continue;
            }

            $value = $response->getHeaderLine($header);

            $data[$field] = match ($field) {
                'ContentLength', 'PartsCount', 'TagCount' => (int) $value,
                'DeleteMarker', 'BucketKeyEnabled' => $value === 'true',
                'LastModified', 'ObjectLockRetainUntilDate' => DateTimeResult::fromTimestamp($value),
                default => $value,
            };
        }

        $metadata = [];

        foreach ($response->getHeaders() as $name => $values) {
            if (str_starts_with($lower = strtolower($name), 'x-amz-meta-')) {
                $metadata[substr($lower, 11)] = implode(', ', $values);
            }
        }

        if ($metadata !== [] || isset($data['ETag'])) {
            $data['Metadata'] = $metadata;
        }

        return $data;
    }

    /**
     * Convert an S3 XML document to an array.
     *
     * @param  \SimpleXMLElement  $element
     * @return array<string, mixed>
     */
    protected function xmlToArray(SimpleXMLElement $element): array
    {
        $data = [];

        foreach ($element->children() as $name => $child) {
            $value = $child->count() > 0 ? $this->xmlToArray($child) : $this->castXmlValue($name, (string) $child);

            if (in_array($name, static::XML_LISTS, true)) {
                $data[$name][] = $value;
            } else {
                $data[$name] = $value;
            }
        }

        return $data;
    }

    /**
     * Cast a scalar XML value to its result type.
     *
     * @param  string  $name
     * @param  string  $value
     * @return mixed
     */
    protected function castXmlValue(string $name, string $value): mixed
    {
        return match ($name) {
            'Size', 'KeyCount', 'MaxKeys', 'PartNumber', 'MaxUploads', 'MaxParts' => (int) $value,
            'IsTruncated', 'IsLatest', 'DeleteMarker', 'Quiet' => $value === 'true',
            'LastModified', 'CreationDate', 'Initiated' => DateTimeResult::fromTimestamp($value),
            default => $value,
        };
    }

    /**
     * Parse an access control policy.
     *
     * @param  \SimpleXMLElement  $xml
     * @return array{Owner: array, Grants: array}
     */
    protected function parseAcl(SimpleXMLElement $xml): array
    {
        $grants = [];

        foreach ($xml->AccessControlList->Grant ?? [] as $grant) {
            $type = (string) ($grant->Grantee->attributes('http://www.w3.org/2001/XMLSchema-instance')['type'] ?? '');

            $grants[] = [
                'Grantee' => array_filter([
                    'Type' => $type,
                    'ID' => isset($grant->Grantee->ID) ? (string) $grant->Grantee->ID : null,
                    'DisplayName' => isset($grant->Grantee->DisplayName) ? (string) $grant->Grantee->DisplayName : null,
                    'URI' => isset($grant->Grantee->URI) ? (string) $grant->Grantee->URI : null,
                    'EmailAddress' => isset($grant->Grantee->EmailAddress) ? (string) $grant->Grantee->EmailAddress : null,
                ], fn ($value) => $value !== null && $value !== ''),
                'Permission' => (string) $grant->Permission,
            ];
        }

        return [
            'Owner' => isset($xml->Owner) ? $this->xmlToArray($xml->Owner) : [],
            'Grants' => $grants,
        ];
    }

    /**
     * Decode the URL encoded keys of a listing.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function decodeListing(array $data): array
    {
        foreach (['Prefix', 'Delimiter', 'StartAfter', 'Marker', 'NextMarker'] as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = rawurldecode($data[$field]);
            }
        }

        foreach ($data['Contents'] ?? [] as $index => $object) {
            $data['Contents'][$index]['Key'] = rawurldecode($object['Key']);
        }

        foreach ($data['CommonPrefixes'] ?? [] as $index => $prefix) {
            $data['CommonPrefixes'][$index]['Prefix'] = rawurldecode($prefix['Prefix']);
        }

        return $data;
    }

    /**
     * Build the XML body of a DeleteObjects request.
     *
     * @param  array{Objects?: array, Quiet?: bool}  $delete
     * @return string
     */
    protected function deleteObjectsXml(array $delete): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><Delete xmlns="http://s3.amazonaws.com/doc/2006-03-01/">';

        foreach ($delete['Objects'] ?? [] as $object) {
            $xml .= '<Object><Key>'.htmlspecialchars($object['Key'], ENT_XML1).'</Key>'
                .(isset($object['VersionId']) ? '<VersionId>'.htmlspecialchars($object['VersionId'], ENT_XML1).'</VersionId>' : '')
                .'</Object>';
        }

        return $xml.(! empty($delete['Quiet']) ? '<Quiet>true</Quiet>' : '').'</Delete>';
    }

    /**
     * Build the XML body of a CompleteMultipartUpload request.
     *
     * @param  array<int, array{PartNumber: int, ETag: string}>  $parts
     * @return string
     */
    protected function completeMultipartXml(array $parts): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><CompleteMultipartUpload xmlns="http://s3.amazonaws.com/doc/2006-03-01/">';

        foreach ($parts as $part) {
            $xml .= '<Part><PartNumber>'.(int) $part['PartNumber'].'</PartNumber><ETag>'.htmlspecialchars((string) $part['ETag'], ENT_XML1).'</ETag></Part>';
        }

        return $xml.'</CompleteMultipartUpload>';
    }

    /**
     * Build the XML body of a CreateBucket request.
     *
     * @param  array<string, mixed>  $args
     * @return string|null
     */
    protected function createBucketXml(array $args): ?string
    {
        $location = $args['CreateBucketConfiguration']['LocationConstraint']
            ?? ($this->region !== 'us-east-1' ? $this->region : null);

        return $location
            ? '<?xml version="1.0" encoding="UTF-8"?><CreateBucketConfiguration xmlns="http://s3.amazonaws.com/doc/2006-03-01/"><LocationConstraint>'.htmlspecialchars($location, ENT_XML1).'</LocationConstraint></CreateBucketConfiguration>'
            : null;
    }

    /**
     * Get the URI of a bucket, or of an object in a bucket.
     *
     * @param  string  $bucket
     * @param  string|null  $key
     * @return \LaraGram\Http\Factory\UriInterface
     */
    protected function objectUri(string $bucket, ?string $key = null): UriInterface
    {
        $path = $key === null ? '' : '/'.$this->encodeKey($key);
        $base = rtrim($this->endpoint->getPath(), '/');

        if ($this->config['bucket_endpoint'] ?? false) {
            return $this->endpoint->withPath($base.($path ?: '/'));
        }

        if ($this->usesPathStyle($bucket)) {
            return $this->endpoint->withPath($base.'/'.$bucket.$path);
        }

        return $this->endpoint
            ->withHost($bucket.'.'.$this->endpoint->getHost())
            ->withPath($base.($path ?: '/'));
    }

    /**
     * Determine if the bucket must be addressed in the path instead of the host.
     *
     * @param  string  $bucket
     * @return bool
     */
    protected function usesPathStyle(string $bucket): bool
    {
        if ($this->config['use_path_style_endpoint'] ?? false) {
            return true;
        }

        $host = $this->endpoint->getHost();

        if (filter_var($host, FILTER_VALIDATE_IP) || $host === 'localhost') {
            return true;
        }

        $valid = (bool) preg_match('/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?$/', $bucket) && strlen($bucket) >= 3 && strlen($bucket) <= 63;

        $dotted = (bool) preg_match('/^[a-z0-9]([a-z0-9\-.]*[a-z0-9])?$/', $bucket) && ! str_contains($bucket, '..');

        return ! ($valid || ($dotted && $this->endpoint->getScheme() === 'http'));
    }

    /**
     * Encode an object key for use in a URI path.
     *
     * @param  string  $key
     * @return string
     */
    protected function encodeKey(string $key): string
    {
        return implode('/', array_map(rawurlencode(...), explode('/', $key)));
    }

    /**
     * Get a required argument.
     *
     * @param  array<string, mixed>  $args
     * @param  string  $name
     * @return string
     */
    protected function requireArg(array $args, string $name): string
    {
        if (! isset($args[$name]) || $args[$name] === '') {
            throw new InvalidArgumentException("The [{$name}] parameter is required.");
        }

        return (string) $args[$name];
    }

    /**
     * Convert a parameter value to a string.
     *
     * @param  mixed  $value
     * @return string
     */
    protected function stringify(mixed $value): string
    {
        return match (true) {
            is_bool($value) => $value ? 'true' : 'false',
            is_array($value) => http_build_query($value, '', '&', PHP_QUERY_RFC3986),
            default => (string) $value,
        };
    }

    /**
     * Convert a date value to a date time.
     *
     * @param  mixed  $value
     * @return \DateTimeInterface
     */
    protected function toDateTime(mixed $value): DateTimeInterface
    {
        return $value instanceof DateTimeInterface ? $value : DateTimeResult::fromTimestamp($value);
    }
}
