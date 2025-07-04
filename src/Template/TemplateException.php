<?php

namespace LaraGram\Template;

use ErrorException;
use LaraGram\Container\Container;
use LaraGram\Support\Reflector;

class TemplateException extends ErrorException
{
    /**
     * Report the exception.
     *
     * @return bool|null
     */
    public function report()
    {
        $exception = $this->getPrevious();

        if (Reflector::isCallable($reportCallable = [$exception, 'report'])) {
            return Container::getInstance()->call($reportCallable);
        }

        return false;
    }
}
