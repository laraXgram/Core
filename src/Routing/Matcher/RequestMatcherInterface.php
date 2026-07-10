<?php

namespace LaraGram\Routing\Matcher;

use LaraGram\Http\BaseRequest;
use LaraGram\Routing\Exceptions\MethodNotAllowedException;
use LaraGram\Routing\Exceptions\NoConfigurationException;
use LaraGram\Routing\Exceptions\ResourceNotFoundException;

interface RequestMatcherInterface
{
    /**
     * Tries to match a request with a set of routes.
     *
     * If the matcher cannot find information, it must throw one of the exceptions documented
     * below.
     *
     * @throws NoConfigurationException  If no routing configuration could be found
     * @throws ResourceNotFoundException If no matching resource could be found
     * @throws MethodNotAllowedException If a matching resource was found but the request method is not allowed
     */
    public function matchRequest(BaseRequest $request): array;
}
