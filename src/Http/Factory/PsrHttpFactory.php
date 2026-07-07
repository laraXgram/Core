<?php

namespace LaraGram\Http\Factory;

use Http\Discovery\Psr17Factory as DiscoveryPsr17Factory;
use Nyholm\Psr7\Factory\Psr17Factory as NyholmPsr17Factory;
use LaraGram\Http\BinaryFileResponse;
use LaraGram\Http\Files\UploadedFile;
use LaraGram\Http\BaseRequest;
use LaraGram\Http\BaseResponse;
use LaraGram\Http\StreamedResponse;

class PsrHttpFactory implements HttpMessageFactoryInterface
{
    private readonly ServerRequestFactoryInterface $serverRequestFactory;
    private readonly StreamFactoryInterface $streamFactory;
    private readonly UploadedFileFactoryInterface $uploadedFileFactory;
    private readonly ResponseFactoryInterface $responseFactory;

    public function __construct(
        ?ServerRequestFactoryInterface $serverRequestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
        ?UploadedFileFactoryInterface $uploadedFileFactory = null,
        ?ResponseFactoryInterface $responseFactory = null,
    ) {
        if (null === $serverRequestFactory || null === $streamFactory || null === $uploadedFileFactory || null === $responseFactory) {
            $psr17Factory = match (true) {
                class_exists(DiscoveryPsr17Factory::class) => new DiscoveryPsr17Factory(),
                class_exists(NyholmPsr17Factory::class) => new NyholmPsr17Factory(),
                default => throw new \LogicException(\sprintf('You cannot use the "%s" as no PSR-17 factories have been provided. Try running "composer require php-http/discovery psr/http-factory-implementation:*".', self::class)),
            };

            $serverRequestFactory ??= $psr17Factory;
            $streamFactory ??= $psr17Factory;
            $uploadedFileFactory ??= $psr17Factory;
            $responseFactory ??= $psr17Factory;
        }

        $this->serverRequestFactory = $serverRequestFactory;
        $this->streamFactory = $streamFactory;
        $this->uploadedFileFactory = $uploadedFileFactory;
        $this->responseFactory = $responseFactory;
    }

    public function createRequest(BaseRequest $laragramRequest): ServerRequestInterface
    {
        $uri = $laragramRequest->server->get('QUERY_STRING', '');
        $uri = $laragramRequest->getSchemeAndHttpHost().$laragramRequest->getBaseUrl().$laragramRequest->getPathInfo().('' !== $uri ? '?'.$uri : '');

        $request = $this->serverRequestFactory->createServerRequest(
            $laragramRequest->getMethod(),
            $uri,
            $laragramRequest->server->all()
        );

        foreach ($laragramRequest->headers->all() as $name => $value) {
            try {
                $request = $request->withHeader($name, $value);
            } catch (\InvalidArgumentException $e) {
                // ignore invalid header
            }
        }

        $body = $this->streamFactory->createStreamFromResource($laragramRequest->getContent(true));
        $format = $laragramRequest->getContentTypeFormat();

        if ('json' === $format) {
            $parsedBody = json_decode($laragramRequest->getContent(), true, 512, \JSON_BIGINT_AS_STRING);

            if (!\is_array($parsedBody)) {
                $parsedBody = null;
            }
        } else {
            $parsedBody = $laragramRequest->request->all();
        }

        $request = $request
            ->withBody($body)
            ->withUploadedFiles($this->getFiles($laragramRequest->files->all()))
            ->withCookieParams($laragramRequest->cookies->all())
            ->withQueryParams($laragramRequest->query->all())
            ->withParsedBody($parsedBody)
        ;

        foreach ($laragramRequest->attributes->all() as $key => $value) {
            $request = $request->withAttribute($key, $value);
        }

        return $request;
    }

    /**
     * Converts Symfony uploaded files array to the PSR one.
     */
    private function getFiles(array $uploadedFiles): array
    {
        $files = [];

        foreach ($uploadedFiles as $key => $value) {
            if (null === $value) {
                $files[$key] = $this->uploadedFileFactory->createUploadedFile($this->streamFactory->createStream(), 0, \UPLOAD_ERR_NO_FILE);
                continue;
            }
            if ($value instanceof UploadedFile) {
                $files[$key] = $this->createUploadedFile($value);
            } else {
                $files[$key] = $this->getFiles($value);
            }
        }

        return $files;
    }

    /**
     * Creates a PSR-7 UploadedFile instance from a Symfony one.
     */
    private function createUploadedFile(UploadedFile $laragramUploadedFile): UploadedFileInterface
    {
        return $this->uploadedFileFactory->createUploadedFile(
            $this->streamFactory->createStreamFromFile(
                $laragramUploadedFile->getRealPath()
            ),
            (int) $laragramUploadedFile->getSize(),
            $laragramUploadedFile->getError(),
            $laragramUploadedFile->getClientOriginalName(),
            $laragramUploadedFile->getClientMimeType()
        );
    }

    public function createResponse(BaseResponse $laragramResponse): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($laragramResponse->getStatusCode(), BaseResponse::$statusTexts[$laragramResponse->getStatusCode()] ?? '');

        if ($laragramResponse instanceof BinaryFileResponse && !$laragramResponse->headers->has('Content-Range')) {
            $stream = $this->streamFactory->createStreamFromFile(
                $laragramResponse->getFile()->getPathname()
            );
        } else {
            $stream = $this->streamFactory->createStreamFromFile('php://temp', 'wb+');
            if ($laragramResponse instanceof StreamedResponse || $laragramResponse instanceof BinaryFileResponse) {
                ob_start(static function ($buffer) use ($stream) {
                    $stream->write($buffer);

                    return '';
                }, 1);

                try {
                    $laragramResponse->sendContent();
                } finally {
                    ob_end_clean();
                }
            } else {
                $stream->write($laragramResponse->getContent());
            }
        }

        $response = $response->withBody($stream);

        $headers = $laragramResponse->headers->all();
        $cookies = $laragramResponse->headers->getCookies();
        if ($cookies) {
            $headers['Set-Cookie'] = [];

            foreach ($cookies as $cookie) {
                $headers['Set-Cookie'][] = $cookie->__toString();
            }
        }

        foreach ($headers as $name => $value) {
            try {
                $response = $response->withHeader($name, $value);
            } catch (\InvalidArgumentException $e) {
                // ignore invalid header
            }
        }

        $protocolVersion = $laragramResponse->getProtocolVersion();

        return $response->withProtocolVersion($protocolVersion);
    }
}
