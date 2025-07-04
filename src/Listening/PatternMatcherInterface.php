<?php

namespace LaraGram\Listening;

use LaraGram\Listening\Exceptions\MethodNotAllowedException;
use LaraGram\Listening\Exceptions\NoConfigurationException;
use LaraGram\Listening\Exceptions\ResourceNotFoundException;

interface PatternMatcherInterface extends RequestContextAwareInterface
{
    /**
     * Tries to match a pattern with a set of listens.
     *
     * If the matcher cannot find information, it must throw one of the exceptions documented
     * below.
     *
     * @param string $pattern The pattern to be parsed
     *
     * @throws NoConfigurationException  If no listening configuration could be found
     * @throws ResourceNotFoundException If the resource could not be found
     * @throws MethodNotAllowedException If the resource was found but the request method is not allowed
     */
    public function match(string $pattern): array;
}
