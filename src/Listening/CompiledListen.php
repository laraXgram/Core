<?php

namespace LaraGram\Listening;

class CompiledListen implements \Serializable
{
    /**
     * @param string      $staticPrefix  The static prefix of the compiled listen
     * @param string      $regex         The regular expression to use to match this listen
     * @param array       $tokens        An array of tokens to use to generate URL for this listen
     * @param array       $variables     An array of variables (variables defined in the path and in the host patterns)
     */
    public function __construct(
        private string $staticPrefix,
        private string $regex,
        private array $tokens,
        private array $variables = [],
    ) {
    }

    public function __serialize(): array
    {
        return [
            'vars' => $this->variables,
            'prefix' => $this->staticPrefix,
            'regex' => $this->regex,
            'tokens' => $this->tokens,
        ];
    }

    /**
     * @internal
     */
    final public function serialize(): string
    {
        throw new \BadMethodCallException('Cannot serialize '.__CLASS__);
    }

    public function __unserialize(array $data): void
    {
        $this->variables = $data['vars'];
        $this->staticPrefix = $data['prefix'];
        $this->regex = $data['regex'];
        $this->tokens = $data['tokens'];
    }

    /**
     * @internal
     */
    final public function unserialize(string $data): void
    {
        $this->__unserialize(unserialize($data, ['allowed_classes' => false]));
    }

    /**
     * Returns the static prefix.
     */
    public function getStaticPrefix(): string
    {
        return $this->staticPrefix;
    }

    /**
     * Returns the regex.
     */
    public function getRegex(): string
    {
        return $this->regex;
    }

    /**
     * Returns the tokens.
     */
    public function getTokens(): array
    {
        return $this->tokens;
    }
    
    /**
     * Returns the variables.
     */
    public function getVariables(): array
    {
        return $this->variables;
    }
}
