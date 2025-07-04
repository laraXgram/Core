<?php

namespace LaraGram\Listening;

use LaraGram\Request\Request;

class RequestContext
{
    private string $pattern;
    private string $method;
    private bool|null $reply;
    private string $scope;
    private array $parameters = [];

    public function __construct(string $pattern = '', string $method = 'TEXT', bool|null $reply = null, string $scope = 'private')
    {
        $this->setPattern($pattern);
        $this->setMethod($method);
        $this->setReply($reply);
        $this->setScope($scope);
    }

    /**
     * Updates the RequestContext information based on a HttpFoundation Request.
     *
     * @return $this
     */
    public function fromRequest(Request $request): static
    {
        $this->setPattern(text() ?? callback_query()->data ?? inline_query()->query ?? chosen_inline_result()->query ?? '');
        $this->setMethod($request->method());
        $this->setReply($request->isReply());
        $this->setScope($request->scope());

        return $this;
    }

    /**
     * Gets the pattern.
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * Sets the pattern.
     *
     * @return $this
     */
    public function setPattern(string $pattern): static
    {
        $this->pattern = $pattern;

        return $this;
    }

    /**
     * Gets the method.
     *
     * The method is always an uppercased string.
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Sets the method.
     *
     * @return $this
     */
    public function setMethod(string $method): static
    {
        $this->method = strtoupper($method);

        return $this;
    }

    /**
     * Gets the is reply.
     *
     */
    public function getReply(): bool|null
    {
        return $this->reply;
    }

    /**
     * Sets the is reply.
     *
     * @return $this
     */
    public function setReply(bool|null $reply): static
    {
        $this->reply = $reply;

        return $this;
    }

    /**
     * Gets the scopes.
     *
     */
    public function getScope(): string
    {
        return $this->scope;
    }

    /**
     * Sets the scopes.
     *
     * @return $this
     */
    public function setScope(string $scope): static
    {
        $this->scope = $scope;

        return $this;
    }

    /**
     * Returns the parameters.
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Sets the parameters.
     *
     * @param array $parameters The parameters
     *
     * @return $this
     */
    public function setParameters(array $parameters): static
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * Gets a parameter value.
     */
    public function getParameter(string $name): mixed
    {
        return $this->parameters[$name] ?? null;
    }

    /**
     * Checks if a parameter value is set for the given parameter.
     */
    public function hasParameter(string $name): bool
    {
        return \array_key_exists($name, $this->parameters);
    }

    /**
     * Sets a parameter value.
     *
     * @return $this
     */
    public function setParameter(string $name, mixed $parameter): static
    {
        $this->parameters[$name] = $parameter;

        return $this;
    }
}
