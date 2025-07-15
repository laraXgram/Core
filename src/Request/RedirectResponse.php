<?php

namespace LaraGram\Request;

use LaraGram\Contracts\Support\MessageProvider;
use LaraGram\Cache\CacheManager as CacheStore;
use LaraGram\Support\MessageBag;
use LaraGram\Support\Str;
use LaraGram\Support\Traits\ForwardsCalls;
use LaraGram\Support\Traits\Macroable;
use LaraGram\Support\TemplateErrorBag;

class RedirectResponse extends Response
{
    use ForwardsCalls, ResponseTrait, Macroable {
        Macroable::__call as macroCall;
    }

    /**
     * The request instance.
     *
     * @var \LaraGram\Request\Request
     */
    protected $request;

    /**
     * The cache store instance.
     *
     * @var \LaraGram\Cache\CacheManager
     */
    protected $cache;

    protected string $targetPath;

    /**
     * Creates a redirect response so that it conforms to the rules defined for a redirect status code.
     *
     * @param string $path     The Path to redirect to. The Path should be a full Path, with prefix etc.
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(string $path)
    {
        parent::__construct();

        $this->setTargetPath($path);
    }

    /**
     * Returns the target Path.
     */
    public function getTargetPath(): string
    {
        return $this->targetPath;
    }

    /**
     * Sets the redirect target of this response.
     *
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function setTargetPath(string $path): static
    {
        if ('' === $path) {
            throw new \InvalidArgumentException('Cannot redirect to an empty Path.');
        }

        $this->targetPath = $path;

        return $this;
    }

    /**
     * Flash a piece of data to the cache.
     *
     * @param  string|array  $key
     * @param  mixed  $value
     * @return $this
     */
    public function with($key, $value = null)
    {
        $key = is_array($key) ? $key : [$key => $value];

        foreach ($key as $k => $v) {
            $this->cache->put($k, $v);
        }

        return $this;
    }

    /**
     * Flash a container of errors to the cache.
     *
     * @param  \LaraGram\Contracts\Support\MessageProvider|array|string  $provider
     * @param  string  $key
     * @return $this
     */
    public function withErrors($provider, $key = 'default')
    {
        $value = $this->parseErrors($provider);

        $errors = $this->cache->get('errors', new TemplateErrorBag);

        if (! $errors instanceof TemplateErrorBag) {
            $errors = new TemplateErrorBag;
        }

        $this->cache->put(
            'errors', $errors->put($key, $value)
        );

        return $this;
    }

    /**
     * Parse the given errors into an appropriate value.
     *
     * @param  \LaraGram\Contracts\Support\MessageProvider|array|string  $provider
     * @return \LaraGram\Support\MessageBag
     */
    protected function parseErrors($provider)
    {
        if ($provider instanceof MessageProvider) {
            return $provider->getMessageBag();
        }

        return new MessageBag((array) $provider);
    }

    /**
     * Get the original response content.
     *
     * @return null
     */
    public function getOriginalContent()
    {
        //
    }

    /**
     * Get the request instance.
     *
     * @return \LaraGram\Request\Request|null
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Set the request instance.
     *
     * @param  \LaraGram\Request\Request  $request
     * @return void
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Get the cache store instance.
     *
     * @return \LaraGram\Cache\CacheManager|null
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Set the cache store instance.
     *
     * @param  \LaraGram\Cache\CacheManager  $cache
     * @return void
     */
    public function setCache(CacheStore $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Dynamically bind flash data in the cache.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        if (str_starts_with($method, 'with')) {
            return $this->with(Str::snake(substr($method, 4)), $parameters[0]);
        }

        static::throwBadMethodCallException($method);
    }
}
