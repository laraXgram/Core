<?php

namespace LaraGram\Listening;

class CompiledPatternMatcher extends PatternMatcher
{
    use CompiledPatternMatcherTrait;

    public function __construct(array $compiledListens, RequestContext $context, array $attributes)
    {
        $this->context = $context;
        $this->attributes = $attributes;
        [$this->staticListens, $this->regexpList, $this->dynamicListens, $this->checkCondition] = $compiledListens;
    }
}
