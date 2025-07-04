<?php

namespace LaraGram\Template;

class TemplateName
{
    /**
     * Normalize the given template name.
     *
     * @param  string  $name
     * @return string
     */
    public static function normalize($name)
    {
        $delimiter = TemplateFinderInterface::HINT_PATH_DELIMITER;

        if (! str_contains($name, $delimiter)) {
            return str_replace('/', '.', $name);
        }

        [$namespace, $name] = explode($delimiter, $name);

        return $namespace.$delimiter.str_replace('/', '.', $name);
    }
}
