<?php

namespace LaraGram\Listening;

use LaraGram\Request\Request;
use LaraGram\Listening\Exceptions\MethodNotAllowedException;
use LaraGram\Listening\Exceptions\NoConfigurationException;
use LaraGram\Listening\Exceptions\ResourceNotFoundException;

interface RequestMatcherInterface
{
    /**
     * Tries to match a request with a set of listens.
     *
     * If the matcher cannot find information, it must throw one of the exceptions documented
     * below.
     *
     * @throws NoConfigurationException  If no listening configuration could be found
     * @throws ResourceNotFoundException If no matching resource could be found
     * @throws MethodNotAllowedException If a matching resource was found but the request method is not allowed
     */
    public function matchRequest(Request $request): array;
}
