<?php

namespace LaraGram\Routing\Matcher;

use LaraGram\Routing\RequestContext;
use LaraGram\Routing\Matcher\Dumper\CompiledUrlMatcherTrait;

class CompiledUrlMatcher extends UrlMatcher
{
    use CompiledUrlMatcherTrait;

    public function __construct(array $compiledRoutes, RequestContext $context)
    {
        $this->context = $context;
        [$this->matchHost, $this->staticRoutes, $this->regexpList, $this->dynamicRoutes, $this->checkCondition] = $compiledRoutes;
    }
}
