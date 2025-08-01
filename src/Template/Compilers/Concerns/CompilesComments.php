<?php

namespace LaraGram\Template\Compilers\Concerns;

trait CompilesComments
{
    /**
     * Compile Temple8 comments into an empty string.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileComments($value)
    {
        $pattern = sprintf('/%s--(.*?)--%s/s', $this->contentTags[0], $this->contentTags[1]);

        return preg_replace($pattern, '', $value);
    }
}
