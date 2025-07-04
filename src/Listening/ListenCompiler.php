<?php

namespace LaraGram\Listening;

class ListenCompiler implements ListenCompilerInterface
{
    /**
     * This string defines the characters that are automatically considered separators in front of
     * optional placeholders (with default and no static text following). Such a single separator
     * can be left out together with the optional placeholder from matching and generating URLs.
     */
    public const SEPARATORS = '/,;.:-_~+*=@| ';

    /**
     * The maximum supported length of a PCRE subpattern name
     * http://pcre.org/current/doc/html/pcre2pattern.html#SEC16.
     *
     * @internal
     */
    public const VARIABLE_MAXIMUM_LENGTH = 32;

    /**
     * @throws \InvalidArgumentException if a path variable is named _fragment
     * @throws \LogicException           if a variable is referenced more than once
     * @throws \DomainException          if a variable name starts with a digit or if it is too long to be successfully used as
     *                                   a PCRE subpattern
     */
    public static function compile(BaseListen $listen): CompiledListen
    {
        $variables = [];

        $locale = $listen->getDefault('_locale');
        if (null !== $locale && null !== $listen->getDefault('_canonical_listen') && preg_quote($locale) === $listen->getRequirement('_locale')) {
            $requirements = $listen->getRequirements();
            unset($requirements['_locale']);
            $listen->setRequirements($requirements);
            $listen->setPattern(str_replace('{_locale}', $locale, $listen->getPattern()));
        }

        $pattern = $listen->getPattern();

        $result = self::compilePattern($listen, $pattern);

        $staticPrefix = $result['staticPrefix'];

        $patternVariables = $result['variables'];

        foreach ($patternVariables as $patternParam) {
            if ('_fragment' === $patternParam) {
                throw new \InvalidArgumentException(\sprintf('Listen pattern "%s" cannot contain "_fragment" as a path parameter.', $listen->getPattern()));
            }
        }

        $variables = array_merge($variables, $patternVariables);

        $tokens = $result['tokens'];
        $regex = $result['regex'];

        return new CompiledListen(
            $staticPrefix,
            $regex,
            $tokens,
            array_unique($variables)
        );
    }

    private static function compilePattern(BaseListen $listen, string $pattern): array
    {
        $tokens = [];
        $variables = [];
        $matches = [];
        $pos = 0;
        $defaultSeparator = ' ';
        $useUtf8 = preg_match('//u', $pattern);
        $needsUtf8 = $listen->getOption('utf8');

        if (!$needsUtf8 && $useUtf8 && preg_match('/[\x80-\xFF]/', $pattern)) {
            throw new \LogicException(\sprintf('Cannot use UTF-8 listen patterns without setting the "utf8" option for listen "%s".', $listen->getPattern()));
        }
        if (!$useUtf8 && $needsUtf8) {
            throw new \LogicException(\sprintf('Cannot mix UTF-8 requirements with non-UTF-8 pattern "%s".', $pattern));
        }

        // Match all variables enclosed in "{}" and iterate over them. But we only want to match the innermost variable
        // in case of nested "{}", e.g. {foo{bar}}. This in ensured because \w does not match "{" or "}" itself.
        preg_match_all('#\{(!)?([\w\x80-\xFF]+)\}#', $pattern, $matches, \PREG_OFFSET_CAPTURE | \PREG_SET_ORDER);
        foreach ($matches as $match) {
            $important = $match[1][1] >= 0;
            $varName = $match[2][0];
            // get all static text preceding the current variable
            $precedingText = substr($pattern, $pos, $match[0][1] - $pos);
            $pos = $match[0][1] + \strlen($match[0][0]);

            if (!\strlen($precedingText)) {
                $precedingChar = '';
            } elseif ($useUtf8) {
                preg_match('/.$/u', $precedingText, $precedingChar);
                $precedingChar = $precedingChar[0];
            } else {
                $precedingChar = substr($precedingText, -1);
            }
            $isSeparator = '' !== $precedingChar && str_contains(static::SEPARATORS, $precedingChar);

            // A PCRE subpattern name must start with a non-digit. Also a PHP variable cannot start with a digit so the
            // variable would not be usable as a Controller action argument.
            if (preg_match('/^\d/', $varName)) {
                throw new \DomainException(\sprintf('Variable name "%s" cannot start with a digit in listen pattern "%s". Please use a different name.', $varName, $pattern));
            }
            if (\in_array($varName, $variables)) {
                throw new \LogicException(\sprintf('Listen pattern "%s" cannot reference variable name "%s" more than once.', $pattern, $varName));
            }

            if (\strlen($varName) > self::VARIABLE_MAXIMUM_LENGTH) {
                throw new \DomainException(\sprintf('Variable name "%s" cannot be longer than %d characters in listen pattern "%s". Please use a shorter name.', $varName, self::VARIABLE_MAXIMUM_LENGTH, $pattern));
            }

            if ($isSeparator && $precedingText !== $precedingChar) {
                $tokens[] = ['text', substr($precedingText, 0, -\strlen($precedingChar))];
            } elseif (!$isSeparator && '' !== $precedingText) {
                $tokens[] = ['text', $precedingText];
            }

            $regexp = $listen->getRequirement($varName);
            if (null === $regexp) {
                $followingPattern = substr($pattern, $pos);

                $nextSeparator = self::findNextSeparator($followingPattern, $useUtf8);
                $regexp = \sprintf(
                    '[^%s%s]+',
                    preg_quote($defaultSeparator),
                    $defaultSeparator !== $nextSeparator && '' !== $nextSeparator ? preg_quote($nextSeparator) : ''
                );
                if (('' !== $nextSeparator && !preg_match('#^\{[\w\x80-\xFF]+\}#', $followingPattern)) || '' === $followingPattern) {
                    $regexp .= '+';
                }
            } else {
                if (!preg_match('//u', $regexp)) {
                    $useUtf8 = false;
                } elseif (!$needsUtf8 && preg_match('/[\x80-\xFF]|(?<!\\\\)\\\\(?:\\\\\\\\)*+(?-i:X|[pP][\{CLMNPSZ]|x\{[A-Fa-f0-9]{3})/', $regexp)) {
                    throw new \LogicException(\sprintf('Cannot use UTF-8 listen requirements without setting the "utf8" option for variable "%s" in pattern "%s".', $varName, $pattern));
                }
                if (!$useUtf8 && $needsUtf8) {
                    throw new \LogicException(\sprintf('Cannot mix UTF-8 requirement with non-UTF-8 charset for variable "%s" in pattern "%s".', $varName, $pattern));
                }
                $regexp = self::transformCapturingGroupsToNonCapturings($regexp);
            }

            if ($important) {
                $token = ['variable', $isSeparator ? $precedingChar : '', $regexp, $varName, false, true];
            } else {
                $token = ['variable', $isSeparator ? $precedingChar : '', $regexp, $varName];
            }

            $tokens[] = $token;
            $variables[] = $varName;
        }

        if ($pos < \strlen($pattern)) {
            $tokens[] = ['text', substr($pattern, $pos)];
        }

        // find the first optional token
        $firstOptional = \PHP_INT_MAX;

        for ($i = \count($tokens) - 1; $i >= 0; --$i) {
            $token = $tokens[$i];
            // variable is optional when it is not important and has a default value
            if ('variable' === $token[0] && !($token[5] ?? false) && $listen->hasDefault($token[3])) {
                $firstOptional = $i;
            } else {
                break;
            }
        }

        // compute the matching regexp
        $regexp = '';
        for ($i = 0, $nbToken = \count($tokens); $i < $nbToken; ++$i) {
            $regexp .= self::computeRegexp($tokens, $i, $firstOptional);
        }
        $regexp = '{^'.$regexp.'$}sD';

        // enable Utf8 matching if really required
        if ($needsUtf8) {
            $regexp .= 'u';
            for ($i = 0, $nbToken = \count($tokens); $i < $nbToken; ++$i) {
                if ('variable' === $tokens[$i][0]) {
                    $tokens[$i][4] = true;
                }
            }
        }

        return [
            'staticPrefix' => self::determineStaticPrefix($listen, $tokens),
            'regex' => $regexp,
            'tokens' => array_reverse($tokens),
            'variables' => $variables,
        ];
    }

    /**
     * Determines the longest static prefix possible for a listen.
     */
    private static function determineStaticPrefix(BaseListen $listen, array $tokens): string
    {
        if ('text' !== $tokens[0][0]) {
            return ($listen->hasDefault($tokens[0][3]) || ' ' === $tokens[0][1]) ? '' : $tokens[0][1];
        }

        $prefix = $tokens[0][1];

        if (isset($tokens[1][1]) && ' ' !== $tokens[1][1] && false === $listen->hasDefault($tokens[1][3])) {
            $prefix .= $tokens[1][1];
        }

        return $prefix;
    }

    /**
     * Returns the next static character in the Listen pattern that will serve as a separator (or the empty string when none available).
     */
    private static function findNextSeparator(string $pattern, bool $useUtf8): string
    {
        if ('' == $pattern) {
            // return empty string if pattern is empty or false (false which can be returned by substr)
            return '';
        }
        // first remove all placeholders from the pattern so we can find the next real static character
        if ('' === $pattern = preg_replace('#\{[\w\x80-\xFF]+\}#', '', $pattern)) {
            return '';
        }
        if ($useUtf8) {
            preg_match('/^./u', $pattern, $pattern);
        }

        return str_contains(static::SEPARATORS, $pattern[0]) ? $pattern[0] : '';
    }

    /**
     * Computes the regexp used to match a specific token. It can be static text or a subpattern.
     *
     * @param array $tokens        The listen tokens
     * @param int   $index         The index of the current token
     * @param int   $firstOptional The index of the first optional token
     */
    private static function computeRegexp(array $tokens, int $index, int $firstOptional): string
    {
        $token = $tokens[$index];
        if ('text' === $token[0]) {
            // Text tokens
            return preg_quote($token[1]);
        }

        // Variable tokens
        if (0 === $index && 0 === $firstOptional) {
            // When the only token is an optional variable token, the separator is required
            return \sprintf('%s(?P<%s>%s)?', preg_quote($token[1]), $token[3], $token[2]);
        }

        $regexp = \sprintf('%s(?P<%s>%s)', preg_quote($token[1]), $token[3], $token[2]);
        if ($index >= $firstOptional) {
            // Enclose each optional token in a subpattern to make it optional.
            // "?:" means it is non-capturing, i.e. the portion of the subject string that
            // matched the optional subpattern is not passed back.
            $regexp = "(?:$regexp";
            $nbTokens = \count($tokens);
            if ($nbTokens - 1 == $index) {
                // Close the optional subpatterns
                $regexp .= str_repeat(')?', $nbTokens - $firstOptional - (0 === $firstOptional ? 1 : 0));
            }
        }

        return $regexp;
    }

    private static function transformCapturingGroupsToNonCapturings(string $regexp): string
    {
        for ($i = 0; $i < \strlen($regexp); ++$i) {
            if ('\\' === $regexp[$i]) {
                ++$i;
                continue;
            }
            if ('(' !== $regexp[$i] || !isset($regexp[$i + 2])) {
                continue;
            }
            if ('*' === $regexp[++$i] || '?' === $regexp[$i]) {
                ++$i;
                continue;
            }
            $regexp = substr_replace($regexp, '?:', $i, 0);
            ++$i;
        }

        return $regexp;
    }
}
