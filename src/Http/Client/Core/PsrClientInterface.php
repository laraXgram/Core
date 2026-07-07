<?php

namespace LaraGram\Http\Client\Core;

use LaraGram\Http\Factory\RequestInterface;
use LaraGram\Http\Factory\ResponseInterface;

interface PsrClientInterface
{
    /**
     * Sends a PSR-7 request and returns a PSR-7 response.
     *
     * @param RequestInterface $request
     *
     * @return ResponseInterface
     *
     * @throws \LaraGram\Http\Client\Core\Exceptions\ClientExceptionInterface If an error happens while processing the request.
     */
    public function sendRequest(RequestInterface $request): ResponseInterface;
}
