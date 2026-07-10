<?php

namespace LaraGram\Http\Exceptions;

use RuntimeException;
use LaraGram\Http\BaseResponse;
use Throwable;

class HttpResponseException extends RuntimeException
{
    /**
     * The underlying response instance.
     *
     * @var \LaraGram\Http\BaseResponse
     */
    protected $response;

    /**
     * Create a new HTTP response exception instance.
     *
     * @param  \LaraGram\Http\BaseResponse  $response
     * @param  \Throwable|null  $previous
     */
    public function __construct(BaseResponse $response, ?Throwable $previous = null)
    {
        parent::__construct($previous?->getMessage() ?? '', $previous?->getCode() ?? 0, $previous);

        $this->response = $response;
    }

    /**
     * Get the underlying response instance.
     *
     * @return \LaraGram\Http\BaseResponse
     */
    public function getResponse()
    {
        return $this->response;
    }
}
