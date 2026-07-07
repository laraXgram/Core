<?php

namespace LaraGram\Http\Factory;

use LaraGram\Http\BaseRequest;
use LaraGram\Http\BaseResponse;

interface HttpFoundationFactoryInterface
{
    /**
     * Creates a Symfony Request instance from a PSR-7 one.
     */
    public function createRequest(ServerRequestInterface $psrRequest, bool $streamed = false): BaseRequest;

    /**
     * Creates a Symfony Response instance from a PSR-7 one.
     */
    public function createResponse(ResponseInterface $psrResponse, bool $streamed = false): BaseResponse;
}
