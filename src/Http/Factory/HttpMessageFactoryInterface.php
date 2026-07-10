<?php

namespace LaraGram\Http\Factory;

use LaraGram\Http\BaseRequest;
use LaraGram\Http\BaseResponse;

interface HttpMessageFactoryInterface
{
    /**
     * Creates a PSR-7 Request instance from a LaraGram one.
     */
    public function createRequest(BaseRequest $laragramRequest): ServerRequestInterface;

    /**
     * Creates a PSR-7 Response instance from a LaraGram one.
     */
    public function createResponse(BaseResponse $laragramRequest): ResponseInterface;
}
