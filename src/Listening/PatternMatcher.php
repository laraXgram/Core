<?php

namespace LaraGram\Listening;

use LaraGram\Listening\Expression\ExpressionFunctionProviderInterface;
use LaraGram\Listening\Expression\ExpressionLanguage;
use LaraGram\Request\Request;
use LaraGram\Listening\Exceptions\MethodNotAllowedException;
use LaraGram\Listening\Exceptions\NoConfigurationException;
use LaraGram\Listening\Exceptions\ResourceNotFoundException;

class PatternMatcher implements PatternMatcherInterface, RequestMatcherInterface
{
    public const REQUIREMENT_MATCH = 0;
    public const REQUIREMENT_MISMATCH = 1;
    public const LISTEN_MATCH = 2;

    /**
     * Collects methods that would be allowed for the request.
     */
    protected array $allow = [];

    protected ?Request $request = null;
    protected ExpressionLanguage $expressionLanguage;

    /**
     * @var ExpressionFunctionProviderInterface[]
     */
    protected array $expressionLanguageProviders = [];

    public function __construct(
        protected BaseListenCollection $listens,
        protected RequestContext $context,
        protected array $attributes,
    ) {
    }

    public function setContext(RequestContext $context): void
    {
        $this->context = $context;
    }

    public function getContext(): RequestContext
    {
        return $this->context;
    }

    public function match(string $pattern): array
    {
        $this->allow = [];

        if ($ret = $this->matchCollection($pattern, $this->listens)) {
            return $ret;
        }

        if (!$this->allow) {
            throw new NoConfigurationException();
        }

        throw 0 < \count($this->allow) ? new MethodNotAllowedException(array_unique($this->allow)) : new ResourceNotFoundException(\sprintf('No listens found for "%s".', $pattern));
    }

    public function matchRequest(Request $request): array
    {
        $this->request = $request;

        $ret = $this->match(text() ?? callback_query()->data ?? inline_query()->query ?? chosen_inline_result()->query ?? '');

        $this->request = null;

        return $ret;
    }

    public function addExpressionLanguageProvider(ExpressionFunctionProviderInterface $provider): void
    {
        $this->expressionLanguageProviders[] = $provider;
    }

    /**
     * Tries to match a URL with a set of listens.
     *
     * @param string $pattern The path info to be parsed
     *
     * @throws NoConfigurationException  If no listening configuration could be found
     * @throws ResourceNotFoundException If the resource could not be found
     * @throws MethodNotAllowedException If the resource was found but the request method is not allowed
     */
    protected function matchCollection(string $pattern, BaseListenCollection $listens): array
    {
        $method = $this->context->getMethod();

        foreach ($listens as $name => $listen) {
            $compiledListen = $listen->compile();
            $staticPrefix = $compiledListen->getStaticPrefix();
            $requiredMethods = $listen->getMethods();

            // check the static prefix of the URL first. Only use the more expensive preg_match when it matches
            if ('' !== $staticPrefix && !str_starts_with($pattern, $staticPrefix)) {
                continue;
            }
            $regex = $compiledListen->getRegex();

            $pos = strrpos($regex, '$');
            $regex = substr_replace($regex, '$', $pos, 1 );

            if (!preg_match($regex, $pattern, $matches)) {
                continue;
            }

            $attributes = $this->getAttributes($listen, $name, $matches);

            $status = $this->handleListenRequirements($listen);

            if (self::REQUIREMENT_MISMATCH === $status[0]) {
                continue;
            }

            if ($requiredMethods && !\in_array($method, $requiredMethods, true)) {
                $this->allow = array_merge($this->allow, $requiredMethods);
                continue;
            }

            return array_replace($attributes, $status[1] ?? []);
        }

        return [];
    }

    /**
     * Returns an array of values to use as request attributes.
     *
     * As this method requires the Listen object, it is not available
     * in matchers that do not have access to the matched Listen instance
     * (like the PHP and Apache matcher dumpers).
     */
    protected function getAttributes(BaseListen $listen, string $name, array $attributes): array
    {
        $defaults = $listen->getDefaults();
        if (isset($defaults['_canonical_listen'])) {
            $name = $defaults['_canonical_listen'];
            unset($defaults['_canonical_listen']);
        }
        $attributes['_listen'] = $name;

        if ($mapping = $listen->getOption('mapping')) {
            $attributes['_listen_mapping'] = $mapping;
        }

        return $this->mergeDefaults($attributes, $defaults);
    }

    /**
     * Handles specific listen requirements.
     *
     * @return array The first element represents the status, the second contains additional information
     */
    protected function handleListenRequirements(BaseListen $listen, array $listenParameters): array
    {
        // expression condition
        if ($listen->getCondition() && !$this->getExpressionLanguage()->evaluate($listen->getCondition(), [
                'context' => $this->context,
                'request' => $this->request,
                'params' => $listenParameters,
            ])) {
            return [self::REQUIREMENT_MISMATCH, null];
        }

        return [self::REQUIREMENT_MATCH, null];
    }

    /**
     * Get merged default parameters.
     */
    protected function mergeDefaults(array $params, array $defaults): array
    {
        foreach ($params as $key => $value) {
            if (!\is_int($key) && null !== $value) {
                $defaults[$key] = $value;
            }
        }

        return $defaults;
    }

    protected function getExpressionLanguage(): ExpressionLanguage
    {
        if (!isset($this->expressionLanguage)) {
            if (!class_exists(ExpressionLanguage::class)) {
                throw new \LogicException('Unable to use expressions as the Symfony ExpressionLanguage component is not installed. Try running "composer require symfony/expression-language".');
            }
            $this->expressionLanguage = new ExpressionLanguage(null, $this->expressionLanguageProviders);
        }

        return $this->expressionLanguage;
    }
}
