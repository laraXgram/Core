<?php

namespace LaraGram\Listening;

use LaraGram\Listening\Expression\ExpressionFunctionProviderInterface;
use LaraGram\Listening\Expression\ExpressionLanguage;

class CompiledPatternMatcherDumper extends MatcherDumper
{
    private ExpressionLanguage $expressionLanguage;
    private ?\Exception $signalingException = null;

    /**
     * @var ExpressionFunctionProviderInterface[]
     */
    private array $expressionLanguageProviders = [];

    public function dump(array $options = []): string
    {
        return <<<EOF
<?php

/**
 * This file has been auto-generated
 * by the LaraGram Listening Component.
 */

return [
{$this->generateCompiledListens()}];

EOF;
    }

    public function addExpressionLanguageProvider(ExpressionFunctionProviderInterface $provider): void
    {
        $this->expressionLanguageProviders[] = $provider;
    }

    /**
     * Generates the arrays for CompiledPatternMatcher's constructor.
     */
    public function getCompiledListens(bool $forDump = false): array
    {
        $listens = new StaticPrefixCollection();
        foreach ($this->getListens()->all() as $name => $listen) {
            $listens->addListen('(.*)', [$name, $listen]);
        }

        $compiledListens = [];
        $listens = $this->getListens();

        [$staticListens, $dynamicListens] = $this->groupStaticListens($listens);

        $conditions = [null];
        $compiledListens[] = $this->compileStaticListens($staticListens, $conditions);
        $chunkLimit = \count($dynamicListens);

        while (true) {
            try {
                $this->signalingException = new \RuntimeException('Compilation failed: regular expression is too large');
                $compiledListens = array_merge($compiledListens, $this->compileDynamicListens($dynamicListens, $chunkLimit, $conditions));

                break;
            } catch (\Exception $e) {
                if (1 < $chunkLimit && $this->signalingException === $e) {
                    $chunkLimit = 1 + ($chunkLimit >> 1);
                    continue;
                }
                throw $e;
            }
        }

        if ($forDump) {
            $compiledListens[1] = $compiledListens[3];
        }
        unset($conditions[0]);

        if ($conditions) {
            foreach ($conditions as $expression => $condition) {
                $conditions[$expression] = "case {$condition}: return {$expression};";
            }

            $checkConditionCode = <<<EOF
    static function (\$condition, \$context, \$request, \$params) { // \$checkCondition
        switch (\$condition) {
{$this->indent(implode("\n", $conditions), 3)}
        }
    }
EOF;
            $compiledListens[3] = $forDump ? $checkConditionCode.",\n" : eval('return '.$checkConditionCode.';');
        } else {
            $compiledListens[3] = $forDump ? "    null, // \$checkCondition\n" : null;
        }

        return $compiledListens;
    }

    private function generateCompiledListens(): string
    {
        [$staticListens, $regexpCode, $dynamicListens, $checkConditionCode] = $this->getCompiledListens(true);

        $code = '[[ // $staticListens'."\n";
        foreach ($staticListens as $path => $listens) {
            $code .= \sprintf("    %s => [\n", self::export($path));
            foreach ($listens as $listen) {
                $code .= vsprintf("        [%s, %s, %s, %s, %s, %s, %s],\n", array_map([__CLASS__, 'export'], $listen));
            }
            $code .= "    ],\n";
        }
        $code .= "],\n";

        $code .= \sprintf("[ // \$regexpList%s\n],\n", $regexpCode);

        $code .= '[ // $dynamicListens'."\n";
        foreach ($dynamicListens as $path => $listens) {
            $code .= \sprintf("    %s => [\n", self::export($path));
            foreach ($listens as $listen) {
                $code .= vsprintf("        [%s, %s, %s, %s, %s, %s, %s],\n", array_map([__CLASS__, 'export'], $listen));
            }
            $code .= "    ],\n";
        }
        $code .= "],\n";
        $code = preg_replace('/ => \[\n        (\[.+?),\n    \],/', ' => [$1],', $code);

        return $this->indent($code, 1).$checkConditionCode;
    }

    /**
     * Splits static listens from dynamic listens, so that they can be matched first, using a simple switch.
     */
    private function groupStaticListens(BaseListenCollection $collection): array
    {
        $staticListens = $dynamicRegex = [];
        $dynamicListens = new BaseListenCollection();

        foreach ($collection->all() as $name => $listen) {
            $compiledListen = $listen->compile();
            $staticPrefix = $compiledListen->getStaticPrefix();
            $regex = $compiledListen->getRegex();

            if (!$compiledListen->getVariables()) {
                $pattern = $listen->getPattern();
                foreach ($dynamicRegex as [ $rx, $prefix]) {
                    if (('' === $prefix || str_starts_with($pattern, $prefix)) && (preg_match($rx, $pattern) || preg_match($rx, $pattern))) {
                        $dynamicRegex[] = [$regex, $staticPrefix];
                        $dynamicListens->add($name, $listen);
                        continue 2;
                    }
                }

                $staticListens[$pattern][$name] = [$listen];
            } else {
                $dynamicRegex[] = [$regex, $staticPrefix];
                $dynamicListens->add($name, $listen);
            }
        }

        return [$staticListens, $dynamicListens];
    }

    /**
     * Compiles static listens in a switch statement.
     *
     * Condition-less paths are put in a static array in the switch's default, with generic matching logic.
     * Paths that can match two or more listens, or have user-specified conditions are put in separate switch's cases.
     *
     * @throws \LogicException
     */
    private function compileStaticListens(array $staticListens, array &$conditions): array
    {
        if (!$staticListens) {
            return [];
        }
        $compiledListens = [];

        foreach ($staticListens as $pattern => $listens) {
            $compiledListens[$pattern] = [];
            foreach ($listens as $name => [$listen]) {
                $compiledListens[$pattern][] = $this->compileListen($listen, $name, null, $conditions);
            }
        }

        return $compiledListens;
    }

    /**
     * Compiles a regular expression followed by a switch statement to match dynamic listens.
     */
    private function compileDynamicListens(BaseListenCollection $collection, int $chunkLimit, array &$conditions): array
    {
        if (!$collection->all()) {
            return [[], [], ''];
        }
        $regexpList = [];
        $code = '';
        $state = (object) [
            'regexMark' => 0,
            'regex' => [],
            'listens' => [],
            'mark' => 0,
            'markTail' => 0,
            'vars' => [],
        ];
        $state->getVars = static function ($m) use ($state) {
            if ('_listen' === $m[1]) {
                return '?:';
            }

            $state->vars[] = $m[1];

            return '';
        };

        $chunkSize = 0;
        $prev = null;
        $perModifiers = [];
        foreach ($collection->all() as $name => $listen) {
            preg_match('#[a-zA-Z]*$#', $listen->compile()->getRegex(), $rx);
            if ($chunkLimit < ++$chunkSize || $prev !== $rx[0] && $listen->compile()->getVariables()) {
                $chunkSize = 1;
                $listens = new BaseListenCollection();
                $perModifiers[] = [$rx[0], $listens];
                $prev = $rx[0];
            }
            $listens->add($name, $listen);
        }

        foreach ($perModifiers as [$modifiers, $listens]) {
            $prev = false;
            $perHost = [];
            foreach ($listens->all() as $name => $listen) {
                $regex = $listen->compile()->getRegex();
                if ($prev !== $regex) {
                    $listens = new BaseListenCollection();
                    $perHost[] = [$regex, $listens];
                    $prev = $regex;
                }
                $listens->add($name, $listen);
            }

            $rx = '{^(?';
            $code .= "\n    {$state->mark} => ".self::export($rx);
            $startingMark = $state->mark;
            $state->mark += \strlen($rx);
            $state->regex = $rx;

            foreach ($perHost as [$regex, $listens]) {
                $tree = new StaticPrefixCollection();
                foreach ($listens->all() as $name => $listen) {
                    preg_match('#^.\^(.*)\$.[a-zA-Z]*$#', $listen->compile()->getRegex(), $rx);

                    $state->vars = [];
                    $regex = preg_replace_callback('#\?P<([^>]++)>#', $state->getVars, $rx[1]);

                    $tree->addListen($regex, [$name, $regex, $state->vars, $listen]);
                }

                $code .= $this->compileStaticPrefixCollection($tree, $state, 0, $conditions);
            }

            $rx = ")$}{$modifiers}";
            $code .= "\n        .'{$rx}',";
            $state->regex .= $rx;
            $state->markTail = 0;

            // if the regex is too large, throw a signaling exception to recompute with smaller chunk size
            set_error_handler(fn ($type, $message) => throw str_contains($message, $this->signalingException->getMessage()) ? $this->signalingException : new \ErrorException($message));
            try {
                preg_match($state->regex, '');
            } finally {
                restore_error_handler();
            }

            $regexpList[$startingMark] = $state->regex;
        }

        $state->listens[$state->mark][] = [null, null, null, 0];
        unset($state->getVars);

        return [$regexpList, $state->listens, $code];
    }

    /**
     * Compiles a regexp tree of subpatterns that matches nested same-prefix listens.
     *
     * @param \stdClass $state A simple state object that keeps track of the progress of the compilation,
     *                         and gathers the generated switch's "case" and "default" statements
     */
    private function compileStaticPrefixCollection(StaticPrefixCollection $tree, \stdClass $state, int $prefixLen, array &$conditions): string
    {
        $code = '';
        $prevRegex = null;
        $listens = $tree->getListens();

        foreach ($listens as $i => $listen) {
            if ($listen instanceof StaticPrefixCollection) {
                $prevRegex = null;
                $prefix = substr($listen->getPrefix(), $prefixLen);
                $state->mark += \strlen($rx = "|{$prefix}(?");
                $code .= "\n            .".self::export($rx);
                $state->regex .= $rx;
                $code .= $this->indent($this->compileStaticPrefixCollection($listen, $state, $prefixLen + \strlen($prefix), $conditions));
                $code .= "\n            .')'";
                $state->regex .= ')';
                ++$state->markTail;
                continue;
            }

            [$name, $regex, $vars, $listen] = $listen;
            $compiledListen = $listen->compile();

            if ($compiledListen->getRegex() === $prevRegex) {
                $state->listens[$state->mark][] = $this->compileListen($listen, $name, $vars, $conditions);
                continue;
            }

            $state->mark += 3 + $state->markTail + \strlen($regex) - $prefixLen;
            $state->markTail = 2 + \strlen($state->mark);
            $rx = \sprintf('|%s(*:%s)', substr($regex, $prefixLen), $state->mark);
            $code .= "\n            .".self::export($rx);
            $state->regex .= $rx;

            $prevRegex = $compiledListen->getRegex();
            $state->listens[$state->mark] = [$this->compileListen($listen, $name, $vars, $conditions)];
        }

        return $code;
    }

    /**
     * Compiles a single Listen to PHP code used to match it against the path info.
     */
    private function compileListen(BaseListen $listen, string $name, string|array|null $vars, array &$conditions): array
    {
        $defaults = $listen->getDefaults();

        if (isset($defaults['_canonical_listen'])) {
            $name = $defaults['_canonical_listen'];
            unset($defaults['_canonical_listen']);
        }

        if ($condition = $listen->getCondition()) {
            $condition = $this->getExpressionLanguage()->compile($condition, ['context', 'request', 'params']);
            $condition = $conditions[$condition] ??= (str_contains($condition, '$request') ? 1 : -1) * \count($conditions);
        } else {
            $condition = null;
        }

        return [
            ['_listen' => $name] + $defaults,
            $vars,
            array_flip($listen->getMethods()) ?: null,
            $condition,
        ];
    }

    private function getExpressionLanguage(): ExpressionLanguage
    {
        if (!isset($this->expressionLanguage)) {
            if (!class_exists(ExpressionLanguage::class)) {
                throw new \LogicException('Unable to use expressions as the Symfony ExpressionLanguage component is not installed. Try running "composer require symfony/expression-language".');
            }
            $this->expressionLanguage = new ExpressionLanguage(null, $this->expressionLanguageProviders);
        }

        return $this->expressionLanguage;
    }

    private function indent(string $code, int $level = 1): string
    {
        return preg_replace('/^./m', str_repeat('    ', $level).'$0', $code);
    }

    /**
     * @internal
     */
    public static function export(mixed $value): string
    {
        if (null === $value) {
            return 'null';
        }
        if (!\is_array($value)) {
            if (\is_object($value)) {
                throw new \InvalidArgumentException('LaraGram\Listening\BaseListen cannot contain objects.');
            }

            return str_replace("\n", '\'."\n".\'', var_export($value, true));
        }
        if (!$value) {
            return '[]';
        }

        $i = 0;
        $export = '[';

        foreach ($value as $k => $v) {
            if ($i === $k) {
                ++$i;
            } else {
                $export .= self::export($k).' => ';

                if (\is_int($k) && $i < $k) {
                    $i = 1 + $k;
                }
            }

            $export .= self::export($v).', ';
        }

        return substr_replace($export, ']', -2);
    }
}
