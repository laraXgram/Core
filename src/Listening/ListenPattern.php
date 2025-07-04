<?php

namespace LaraGram\Listening;

class ListenPattern
{
    /**
     * The listen pattern.
     *
     * @var string
     */
    public $pattern;

    /**
     * The fields that should be used when resolving bindings.
     *
     * @var array
     */
    public $bindingFields = [];

    /**
     * Create a new listen URI instance.
     *
     * @param  string  $pattern
     * @param  array  $bindingFields
     * @return void
     */
    public function __construct(string $pattern, array $bindingFields = [])
    {
        $this->pattern = $pattern;
        $this->bindingFields = $bindingFields;
    }

    /**
     * Parse the given URI.
     *
     * @param  string  $pattern
     * @return static
     */
    public static function parse($pattern)
    {
        preg_match_all('/\{([\w\:]+?)\??\}/', $pattern, $matches);

        $bindingFields = [];

        foreach ($matches[0] as $match) {
            if (! str_contains($match, ':')) {
                continue;
            }

            $segments = explode(':', trim($match, '{}?'));

            $bindingFields[$segments[0]] = $segments[1];

            $pattern = str_contains($match, '?')
                ? str_replace($match, '{'.$segments[0].'?}', $pattern)
                : str_replace($match, '{'.$segments[0].'}', $pattern);
        }

        return new static($pattern, $bindingFields);
    }
}
