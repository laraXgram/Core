<?php

namespace LaraGram\Listening;

use LaraGram\Listening\Exceptions\MethodNotAllowedException;
use LaraGram\Listening\Exceptions\ResourceNotFoundException;
use LaraGram\Listening\Matcher\RedirectablePatternMatcherInterface;
use LaraGram\Support\Arr;

trait CompiledPatternMatcherTrait
{
    private array $staticListens = [];
    private array $regexpList = [];
    private array $dynamicListens = [];
    private ?\Closure $checkCondition;

    public function match(string $pattern): array
    {
        $allow = [];

        if ($ret = $this->doMatch($pattern, $allow)) {
            return $ret;
        }
        if ($allow) {
            throw new MethodNotAllowedException(array_keys($allow));
        }
        if (!$this instanceof RedirectableUrlMatcherInterface) {
            throw new ResourceNotFoundException(\sprintf('No listens found for "%s".', $pattern));
        }
        try {
            if ($ret = $this->doMatch($pattern)) {
                return $this->redirect($pattern, $ret['_listen']) + $ret;
            }
        } catch (\Exception) {
            //
        }

        throw new ResourceNotFoundException(\sprintf('No listens found for "%s".', $pattern));
    }

    private function doMatch(string $pattern, array &$allow = []): array
    {
        $allow = [];
        $context = $this->context;
        $requestMethod = $canonicalMethod = $context->getMethod();

        foreach ($this->staticListens[$pattern] ?? [] as [$ret, , $requiredMethods, $condition]) {
            if ($condition && !($this->checkCondition)($condition, $context, 0 < $condition ? $request ??= $this->request ?: null : null, $ret)) {
                continue;
            }

            if ($requiredMethods && !isset($requiredMethods[$canonicalMethod]) && !isset($requiredMethods[$requestMethod])) {
                $allow[] = $requiredMethods;
                continue;
            }

            $attribute = $this->attributes[$ret['_listen']];
            $allow_scopes = Arr::wrap($attribute['action']['scope'] ?? []);
            $has_reply = $attribute['action']['reply'] ?? null;

            if ($allow_scopes !== [] && !in_array(strtolower($this->context->getScope()), $allow_scopes)){
                continue;
            }

            if ($has_reply !== null && $this->context->getReply() !== $has_reply){
                continue;
            }

            return $ret;
        }

        foreach ($this->regexpList as $offset => $regex) {
            while (preg_match($regex, $pattern, $matches)) {
                foreach ($this->dynamicListens[$m = (int)$matches['MARK']] as [$ret, $vars, $requiredMethods, $condition]) {
                    if (0 === $condition) { // marks the last listen in the regexp
                        continue 3;
                    }

                    foreach ($vars as $i => $v) {
                        if (isset($matches[1 + $i])) {
                            $ret[$v] = $matches[1 + $i];
                        }
                    }

                    if ($condition && !($this->checkCondition)($condition, $context, 0 < $condition ? $request ??= $this->request ?: null : null, $ret)) {
                        continue;
                    }

                    if ($requiredMethods && !isset($requiredMethods[$canonicalMethod]) && !isset($requiredMethods[$requestMethod])) {
                        $allow += $requiredMethods;
                        continue;
                    }

                    $attribute = $this->attributes[$ret['_listen']];
                    $allow_scopes = Arr::wrap($attribute['action']['scope'] ?? []);
                    $has_reply = $attribute['action']['reply'] ?? null;

                    if ($allow_scopes !== [] && !in_array(strtolower($this->context->getScope()), $allow_scopes)){
                        continue;
                    }

                    if ($has_reply !== null && $this->context->getReply() !== $has_reply){
                        continue;
                    }

                    return $ret;
                }

                $regex = substr_replace($regex, 'F', $m - $offset, 1 + \strlen($m));
                $offset += \strlen($m);
            }
        }

        return [];
    }
}
