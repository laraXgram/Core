<?php

namespace LaraGram\Request\Exceptions;

use LaraGram\Request\Response;
use RuntimeException;
use Throwable;

class RequestResponseException extends RuntimeException
{
    /**
     * The underlying response instance.
     *
     * @var Response $response
     */
    protected $response;

    /**
     * Create a new HTTP response exception instance.
     *
     * @param  Response  $response
     * @param  \Throwable  $previous
     * @return void
     */
    public function __construct(Response $response, ?Throwable $previous = null)
    {
        parent::__construct($previous?->getMessage() ?? '', $previous?->getCode() ?? 0, $previous);

        $this->response = $response;
    }

    /**
     * Get the underlying response instance.
     *
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }
}
