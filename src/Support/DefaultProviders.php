<?php

namespace LaraGram\Support;

class DefaultProviders
{
    /**
     * The current providers.
     *
     * @var array
     */
    protected $providers;

    /**
     * Create a new default provider collection.
     *
     * @return void
     */
    public function __construct(?array $providers = null)
    {
        $this->providers = $providers ?: [
            \LaraGram\Foundation\Providers\ConsoleSupportServiceProvider::class,
            \LaraGram\Cache\CacheServiceProvider::class,
            \LaraGram\Listener\ListenerServiceProvider::class,
            \LaraGram\Request\RequestServiceProvider::class,
            \LaraGram\Database\DatabaseServiceProvider::class,
            \LaraGram\Redis\RedisServiceProvider::class,
            \LaraGram\Auth\AuthServiceProvider::class,
            \LaraGram\Keyboard\KeyboardServiceProvider::class,
            \LaraGram\Conversation\ConversationServiceProvider::class,
        ];
    }

    /**
     * Merge the given providers into the provider collection.
     *
     * @param  array  $providers
     * @return static
     */
    public function merge(array $providers)
    {
        $this->providers = array_merge($this->providers, $providers);

        return new static($this->providers);
    }

    /**
     * Replace the given providers with other providers.
     *
     * @param  array  $replacements
     * @return static
     */
    public function replace(array $replacements)
    {
        $current = $this->providers;

        foreach ($replacements as $from => $to) {
            $key = array_search($from, $current, true);

            if (is_int($key)) {
                $current[$key] = $to;
            }
        }

        return new static(array_values($current));
    }

    /**
     * Disable the given providers.
     *
     * @param  array  $providers
     * @return static
     */
    public function except(array $providers)
    {
        return new static(array_values(array_filter($this->providers, fn($p) => !in_array($p, $providers))));
    }

    /**
     * Convert the provider collection to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->providers;
    }
}
