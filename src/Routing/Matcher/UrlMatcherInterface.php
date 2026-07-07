<?php

namespace LaraGram\Routing\Matcher;

use LaraGram\Routing\Exceptions\MethodNotAllowedException;
use LaraGram\Routing\Exceptions\NoConfigurationException;
use LaraGram\Routing\Exceptions\ResourceNotFoundException;
use LaraGram\Routing\RequestContextAwareInterface;

interface UrlMatcherInterface extends RequestContextAwareInterface
{
    /**
     * Tries to match a URL path with a set of routes.
     *
     * If the matcher cannot find information, it must throw one of the exceptions documented
     * below.
     *
     * @param string $pathinfo The path info to be parsed (raw format, i.e. not urldecoded)
     *
     * @throws NoConfigurationException  If no routing configuration could be found
     * @throws ResourceNotFoundException If the resource could not be found
     * @throws MethodNotAllowedException If the resource was found but the request method is not allowed
     */
    public function match(string $pathinfo): array;
}
