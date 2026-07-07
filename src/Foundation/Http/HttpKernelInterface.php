<?php

namespace LaraGram\Foundation\Http;

use LaraGram\Http\BaseRequest;
use LaraGram\Http\BaseResponse;

interface HttpKernelInterface
{
    public const HTTP_MAIN_REQUEST = 1;
    public const HTTP_SUB_REQUEST = 2;

    /**
     * Handles a Request to convert it to a Response.
     *
     * When $catch is true, the implementation must catch all exceptions
     * and do its best to convert them to a Response instance.
     *
     * @param int  $type  The type of the request
     *                    (one of HttpKernelInterface::HTTP_MAIN_REQUEST or HttpKernelInterface::HTTP_SUB_REQUEST)
     * @param bool $catch Whether to catch exceptions or not
     *
     * @throws \Exception When an Exception occurs during processing
     */
    public function handleHttp(BaseRequest $request, int $type = self::HTTP_MAIN_REQUEST, bool $catch = true): BaseResponse;
}
