<?php

namespace LaraGram\Request;

use LaraGram\Request\Exceptions\RequestResponseException;
use Throwable;

trait ResponseTrait
{
    /**
     * The original content of the response.
     *
     * @var mixed
     */
    public $original;

    /**
     * The exception that triggered the error response (if applicable).
     *
     * @var \Throwable|null
     */
    public $exception;

    /**
     * Get the content of the response.
     *
     * @return string
     */
    public function content()
    {
        return $this->getContent();
    }

    /**
     * Get the original response content.
     *
     * @return mixed
     */
    public function getOriginalContent()
    {
        $original = $this->original;

        return $original instanceof self ? $original->{__FUNCTION__}() : $original;
    }

    /**
     * Get the callback of the response.
     *
     * @return string|null
     */
    public function getCallback()
    {
        return $this->callback ?? null;
    }

    /**
     * Set the exception to attach to the response.
     *
     * @param  \Throwable  $e
     * @return $this
     */
    public function withException(Throwable $e)
    {
        $this->exception = $e;

        return $this;
    }

    /**
     * Throws the response in a HttpResponseException instance.
     *
     * @return never
     *
     * @throws \LaraGram\Request\Exceptions\RequestResponseException
     */
    public function throwResponse()
    {
        throw new RequestResponseException($this);
    }
}
