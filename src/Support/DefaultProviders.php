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
            \LaraGram\Auth\AuthServiceProvider::class,
            \LaraGram\Bus\BusServiceProvider::class,
            \LaraGram\Cache\CacheServiceProvider::class,
            \LaraGram\Conversation\ConversationServiceProvider::class,
            \LaraGram\Foundation\Providers\ConsoleSupportServiceProvider::class,
            \LaraGram\Concurrency\ConcurrencyServiceProvider::class,
            \LaraGram\Database\DatabaseServiceProvider::class,
            \LaraGram\Encryption\EncryptionServiceProvider::class,
            \LaraGram\Filesystem\FilesystemServiceProvider::class,
            \LaraGram\Foundation\Providers\FoundationServiceProvider::class,
            \LaraGram\Hashing\HashServiceProvider::class,
            \LaraGram\Keyboard\KeyboardServiceProvider::class,
            \LaraGram\Pipeline\PipelineServiceProvider::class,
            \LaraGram\Queue\QueueServiceProvider::class,
            \LaraGram\Redis\RedisServiceProvider::class,
            \LaraGram\Template\TemplateServiceProvider::class,
            \LaraGram\Translation\TranslationServiceProvider::class,
            \LaraGram\Validation\ValidationServiceProvider::class,
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
        $current = new Collection($this->providers);

        foreach ($replacements as $from => $to) {
            $key = $current->search($from);

            $current = is_int($key) ? $current->replace([$key => $to]) : $current;
        }

        return new static($current->values()->toArray());
    }

    /**
     * Disable the given providers.
     *
     * @param  array  $providers
     * @return static
     */
    public function except(array $providers)
    {
        return new static((new Collection($this->providers))
            ->reject(fn ($p) => in_array($p, $providers))
            ->values()
            ->toArray());
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
