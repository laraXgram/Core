<?php

namespace LaraGram\Template;

use LaraGram\Contracts\Container\Container;
use LaraGram\Contracts\Events\Dispatcher;
use LaraGram\Contracts\Support\Arrayable;
use LaraGram\Contracts\Template\Factory as FactoryContract;
use LaraGram\Support\Arr;
use LaraGram\Support\Traits\Macroable;
use LaraGram\Template\Engines\EngineResolver;
use InvalidArgumentException;

class Factory implements FactoryContract
{
    use Macroable,
        Concerns\ManagesComponents,
        Concerns\ManagesEvents,
        Concerns\ManagesFragments,
        Concerns\ManagesLayouts,
        Concerns\ManagesLoops,
        Concerns\ManagesStacks,
        Concerns\ManagesTranslations;

    /**
     * The engine implementation.
     *
     * @var \LaraGram\Template\Engines\EngineResolver
     */
    protected $engines;

    /**
     * The template finder implementation.
     *
     * @var \LaraGram\Template\TemplateFinderInterface
     */
    protected $finder;

    /**
     * The event dispatcher instance.
     *
     * @var \LaraGram\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * The IoC container instance.
     *
     * @var \LaraGram\Contracts\Container\Container
     */
    protected $container;

    /**
     * Data that should be available to all templates.
     *
     * @var array
     */
    protected $shared = [];

    /**
     * The extension to engine bindings.
     *
     * @var array
     */
    protected $extensions = [
        't8.php' => 'temple8',
        'php' => 'php',
        'css' => 'file',
        'html' => 'file',
    ];

    /**
     * The template composer events.
     *
     * @var array
     */
    protected $composers = [];

    /**
     * The number of active rendering operations.
     *
     * @var int
     */
    protected $renderCount = 0;

    /**
     * The "once" block IDs that have been rendered.
     *
     * @var array
     */
    protected $renderedOnce = [];

    /**
     * The cached array of engines for paths.
     *
     * @var array
     */
    protected $pathEngineCache = [];

    /**
     * The cache of normalized names for templates.
     *
     * @var array
     */
    protected $normalizedNameCache = [];

    /**
     * Create a new template factory instance.
     *
     * @param  \LaraGram\Template\Engines\EngineResolver  $engines
     * @param  \LaraGram\Template\TemplateFinderInterface  $finder
     * @param  \LaraGram\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function __construct(EngineResolver $engines, TemplateFinderInterface $finder, Dispatcher $events)
    {
        $this->finder = $finder;
        $this->events = $events;
        $this->engines = $engines;

        $this->share('__env', $this);
    }

    /**
     * Get the evaluated template contents for the given template.
     *
     * @param  string  $path
     * @param  \LaraGram\Contracts\Support\Arrayable|array  $data
     * @param  array  $mergeData
     * @return \LaraGram\Contracts\Template\Template
     */
    public function file($path, $data = [], $mergeData = [])
    {
        $data = array_merge($mergeData, $this->parseData($data));

        return tap($this->templateInstance($path, $path, $data), function ($template) {
            $this->callCreator($template);
        });
    }

    /**
     * Get the evaluated template contents for the given template.
     *
     * @param  string  $template
     * @param  \LaraGram\Contracts\Support\Arrayable|array  $data
     * @param  array  $mergeData
     * @return \LaraGram\Contracts\Template\Template
     */
    public function make($template, $data = [], $mergeData = [])
    {
        $path = $this->finder->find(
            $template = $this->normalizeName($template)
        );

        // Next, we will create the template instance and call the template creator for the template
        // which can set any data, etc. Then we will return the template instance back to
        // the caller for rendering or performing other template manipulations on this.
        $data = array_merge($mergeData, $this->parseData($data));

        return tap($this->templateInstance($template, $path, $data), function ($template) {
            $this->callCreator($template);
        });
    }

    /**
     * Get the first template that actually exists from the given list.
     *
     * @param  array  $templates
     * @param  \LaraGram\Contracts\Support\Arrayable|array  $data
     * @param  array  $mergeData
     * @return \LaraGram\Contracts\Template\Template
     *
     * @throws \InvalidArgumentException
     */
    public function first(array $templates, $data = [], $mergeData = [])
    {
        $template = Arr::first($templates, function ($template) {
            return $this->exists($template);
        });

        if (! $template) {
            throw new InvalidArgumentException('None of the templates in the given array exist.');
        }

        return $this->make($template, $data, $mergeData);
    }

    /**
     * Get the rendered content of the template based on a given condition.
     *
     * @param  bool  $condition
     * @param  string  $template
     * @param  \LaraGram\Contracts\Support\Arrayable|array  $data
     * @param  array  $mergeData
     * @return string
     */
    public function renderWhen($condition, $template, $data = [], $mergeData = [])
    {
        if (! $condition) {
            return '';
        }

        return $this->make($template, $this->parseData($data), $mergeData)->render();
    }

    /**
     * Get the rendered content of the template based on the negation of a given condition.
     *
     * @param  bool  $condition
     * @param  string  $template
     * @param  \LaraGram\Contracts\Support\Arrayable|array  $data
     * @param  array  $mergeData
     * @return string
     */
    public function renderUnless($condition, $template, $data = [], $mergeData = [])
    {
        return $this->renderWhen(! $condition, $template, $data, $mergeData);
    }

    /**
     * Get the rendered contents of a partial from a loop.
     *
     * @param  string  $template
     * @param  array  $data
     * @param  string  $iterator
     * @param  string  $empty
     * @return string
     */
    public function renderEach($template, $data, $iterator, $empty = 'raw|')
    {
        $result = '';

        // If is actually data in the array, we will loop through the data and append
        // an instance of the partial template to the final result HTML passing in the
        // iterated value of this data array, allowing the templates to access them.
        if (count($data) > 0) {
            foreach ($data as $key => $value) {
                $result .= $this->make(
                    $template, ['key' => $key, $iterator => $value]
                )->render();
            }
        }

        // If there is no data in the array, we will render the contents of the empty
        // template. Alternatively, the "empty template" could be a raw string that begins
        // with "raw|" for convenience and to let this know that it is a string.
        else {
            $result = str_starts_with($empty, 'raw|')
                        ? substr($empty, 4)
                        : $this->make($empty)->render();
        }

        return $result;
    }

    /**
     * Normalize a template name.
     *
     * @param  string  $name
     * @return string
     */
    protected function normalizeName($name)
    {
        return $this->normalizedNameCache[$name] ??= TemplateName::normalize($name);
    }

    /**
     * Parse the given data into a raw array.
     *
     * @param  mixed  $data
     * @return array
     */
    protected function parseData($data)
    {
        return $data instanceof Arrayable ? $data->toArray() : $data;
    }

    /**
     * Create a new template instance from the given arguments.
     *
     * @param  string  $template
     * @param  string  $path
     * @param  \LaraGram\Contracts\Support\Arrayable|array  $data
     * @return \LaraGram\Contracts\Template\Template
     */
    protected function templateInstance($template, $path, $data)
    {
        return new Template($this, $this->getEngineFromPath($path), $template, $path, $data);
    }

    /**
     * Determine if a given template exists.
     *
     * @param  string  $template
     * @return bool
     */
    public function exists($template)
    {
        try {
            $this->finder->find($template);
        } catch (InvalidArgumentException) {
            return false;
        }

        return true;
    }

    /**
     * Get the appropriate template engine for the given path.
     *
     * @param  string  $path
     * @return \LaraGram\Contracts\Template\Engine
     *
     * @throws \InvalidArgumentException
     */
    public function getEngineFromPath($path)
    {
        if (isset($this->pathEngineCache[$path])) {
            return $this->engines->resolve($this->pathEngineCache[$path]);
        }

        if (! $extension = $this->getExtension($path)) {
            throw new InvalidArgumentException("Unrecognized extension in file: {$path}.");
        }

        return $this->engines->resolve(
            $this->pathEngineCache[$path] = $this->extensions[$extension]
        );
    }

    /**
     * Get the extension used by the template file.
     *
     * @param  string  $path
     * @return string|null
     */
    protected function getExtension($path)
    {
        $extensions = array_keys($this->extensions);

        return Arr::first($extensions, function ($value) use ($path) {
            return str_ends_with($path, '.'.$value);
        });
    }

    /**
     * Add a piece of shared data to the environment.
     *
     * @param  array|string  $key
     * @param  mixed|null  $value
     * @return mixed
     */
    public function share($key, $value = null)
    {
        $keys = is_array($key) ? $key : [$key => $value];

        foreach ($keys as $key => $value) {
            $this->shared[$key] = $value;
        }

        return $value;
    }

    /**
     * Increment the rendering counter.
     *
     * @return void
     */
    public function incrementRender()
    {
        $this->renderCount++;
    }

    /**
     * Decrement the rendering counter.
     *
     * @return void
     */
    public function decrementRender()
    {
        $this->renderCount--;
    }

    /**
     * Check if there are no active render operations.
     *
     * @return bool
     */
    public function doneRendering()
    {
        return $this->renderCount == 0;
    }

    /**
     * Determine if the given once token has been rendered.
     *
     * @param  string  $id
     * @return bool
     */
    public function hasRenderedOnce(string $id)
    {
        return isset($this->renderedOnce[$id]);
    }

    /**
     * Mark the given once token as having been rendered.
     *
     * @param  string  $id
     * @return void
     */
    public function markAsRenderedOnce(string $id)
    {
        $this->renderedOnce[$id] = true;
    }

    /**
     * Add a location to the array of template locations.
     *
     * @param  string  $location
     * @return void
     */
    public function addLocation($location)
    {
        $this->finder->addLocation($location);
    }

    /**
     * Prepend a location to the array of template locations.
     *
     * @param  string  $location
     * @return void
     */
    public function prependLocation($location)
    {
        $this->finder->prependLocation($location);
    }

    /**
     * Add a new namespace to the loader.
     *
     * @param  string  $namespace
     * @param  string|array  $hints
     * @return $this
     */
    public function addNamespace($namespace, $hints)
    {
        $this->finder->addNamespace($namespace, $hints);

        return $this;
    }

    /**
     * Prepend a new namespace to the loader.
     *
     * @param  string  $namespace
     * @param  string|array  $hints
     * @return $this
     */
    public function prependNamespace($namespace, $hints)
    {
        $this->finder->prependNamespace($namespace, $hints);

        return $this;
    }

    /**
     * Replace the namespace hints for the given namespace.
     *
     * @param  string  $namespace
     * @param  string|array  $hints
     * @return $this
     */
    public function replaceNamespace($namespace, $hints)
    {
        $this->finder->replaceNamespace($namespace, $hints);

        return $this;
    }

    /**
     * Register a valid template extension and its engine.
     *
     * @param  string  $extension
     * @param  string  $engine
     * @param  \Closure|null  $resolver
     * @return void
     */
    public function addExtension($extension, $engine, $resolver = null)
    {
        $this->finder->addExtension($extension);

        if (isset($resolver)) {
            $this->engines->register($engine, $resolver);
        }

        unset($this->extensions[$extension]);

        $this->extensions = array_merge([$extension => $engine], $this->extensions);

        $this->pathEngineCache = [];
    }

    /**
     * Flush all of the factory state like sections and stacks.
     *
     * @return void
     */
    public function flushState()
    {
        $this->renderCount = 0;
        $this->renderedOnce = [];

        $this->flushSections();
        $this->flushStacks();
        $this->flushComponents();
        $this->flushFragments();
    }

    /**
     * Flush all of the section contents if done rendering.
     *
     * @return void
     */
    public function flushStateIfDoneRendering()
    {
        if ($this->doneRendering()) {
            $this->flushState();
        }
    }

    /**
     * Get the extension to engine bindings.
     *
     * @return array
     */
    public function getExtensions()
    {
        return $this->extensions;
    }

    /**
     * Get the engine resolver instance.
     *
     * @return \LaraGram\Template\Engines\EngineResolver
     */
    public function getEngineResolver()
    {
        return $this->engines;
    }

    /**
     * Get the template finder instance.
     *
     * @return \LaraGram\Template\TemplateFinderInterface
     */
    public function getFinder()
    {
        return $this->finder;
    }

    /**
     * Set the template finder instance.
     *
     * @param  \LaraGram\Template\TemplateFinderInterface  $finder
     * @return void
     */
    public function setFinder(TemplateFinderInterface $finder)
    {
        $this->finder = $finder;
    }

    /**
     * Flush the cache of templates located by the finder.
     *
     * @return void
     */
    public function flushFinderCache()
    {
        $this->getFinder()->flush();
    }

    /**
     * Get the event dispatcher instance.
     *
     * @return \LaraGram\Contracts\Events\Dispatcher
     */
    public function getDispatcher()
    {
        return $this->events;
    }

    /**
     * Set the event dispatcher instance.
     *
     * @param  \LaraGram\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function setDispatcher(Dispatcher $events)
    {
        $this->events = $events;
    }

    /**
     * Get the IoC container instance.
     *
     * @return \LaraGram\Contracts\Container\Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Set the IoC container instance.
     *
     * @param  \LaraGram\Contracts\Container\Container  $container
     * @return void
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Get an item from the shared data.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function shared($key, $default = null)
    {
        return Arr::get($this->shared, $key, $default);
    }

    /**
     * Get all of the shared data for the environment.
     *
     * @return array
     */
    public function getShared()
    {
        return $this->shared;
    }
}
