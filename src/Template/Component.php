<?php

namespace LaraGram\Template;

use Closure;
use LaraGram\Container\Container;
use LaraGram\Contracts\Support\Htmlable;
use LaraGram\Contracts\Template\Template as TemplateContract;
use LaraGram\Support\Collection;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

abstract class Component
{
    /**
     * The properties / methods that should not be exposed to the component.
     *
     * @var array
     */
    protected $except = [];

    /**
     * The component alias name.
     *
     * @var string
     */
    public $componentName;

    /**
     * The component attributes.
     *
     * @var \LaraGram\Template\ComponentAttributeBag
     */
    public $attributes;

    /**
     * The template factory instance, if any.
     *
     * @var \LaraGram\Contracts\Template\Factory|null
     */
    protected static $factory;

    /**
     * The component resolver callback.
     *
     * @var (\Closure(string, array): Component)|null
     */
    protected static $componentsResolver;

    /**
     * The cache of Temple8 template names, keyed by contents.
     *
     * @var array<string, string>
     */
    protected static $Temple8TemplateCache = [];

    /**
     * The cache of public property names, keyed by class.
     *
     * @var array
     */
    protected static $propertyCache = [];

    /**
     * The cache of public method names, keyed by class.
     *
     * @var array
     */
    protected static $methodCache = [];

    /**
     * The cache of constructor parameters, keyed by class.
     *
     * @var array<class-string, array<int, string>>
     */
    protected static $constructorParametersCache = [];

    /**
     * The cache of ignored parameter names.
     *
     * @var array
     */
    protected static $ignoredParameterNames = [];

    /**
     * Get the template / template contents that represent the component.
     *
     * @return \LaraGram\Contracts\Template\Template|\LaraGram\Contracts\Support\Htmlable|\Closure|string
     */
    abstract public function render();

    /**
     * Resolve the component instance with the given data.
     *
     * @param  array  $data
     * @return static
     */
    public static function resolve($data)
    {
        if (static::$componentsResolver) {
            return call_user_func(static::$componentsResolver, static::class, $data);
        }

        $parameters = static::extractConstructorParameters();

        $dataKeys = array_keys($data);

        if (empty(array_diff($parameters, $dataKeys))) {
            return new static(...array_intersect_key($data, array_flip($parameters)));
        }

        return Container::getInstance()->make(static::class, $data);
    }

    /**
     * Extract the constructor parameters for the component.
     *
     * @return array
     */
    protected static function extractConstructorParameters()
    {
        if (! isset(static::$constructorParametersCache[static::class])) {
            $class = new ReflectionClass(static::class);

            $constructor = $class->getConstructor();

            static::$constructorParametersCache[static::class] = $constructor
                ? (new Collection($constructor->getParameters()))->map->getName()->all()
                : [];
        }

        return static::$constructorParametersCache[static::class];
    }

    /**
     * Resolve the Temple8 template or template file that should be used when rendering the component.
     *
     * @return \LaraGram\Contracts\Template\Template|\LaraGram\Contracts\Support\Htmlable|\Closure|string
     */
    public function resolveTemplate()
    {
        $template = $this->render();

        if ($template instanceof TemplateContract) {
            return $template;
        }

        if ($template instanceof Htmlable) {
            return $template;
        }

        $resolver = function ($template) {
            if ($template instanceof TemplateContract) {
                return $template;
            }

            return $this->extractTemple8TemplateFromString($template);
        };

        return $template instanceof Closure ? function (array $data = []) use ($template, $resolver) {
            return $resolver($template($data));
        }
        : $resolver($template);
    }

    /**
     * Create a Temple8 template with the raw component string content.
     *
     * @param  string  $contents
     * @return string
     */
    protected function extractTemple8TemplateFromString($contents)
    {
        $key = sprintf('%s::%s', static::class, $contents);

        if (isset(static::$Temple8TemplateCache[$key])) {
            return static::$Temple8TemplateCache[$key];
        }

        if ($this->factory()->exists($contents)) {
            return static::$Temple8TemplateCache[$key] = $contents;
        }

        return static::$Temple8TemplateCache[$key] = $this->createTemple8TemplateFromString($this->factory(), $contents);
    }

    /**
     * Create a Temple8 template with the raw component string content.
     *
     * @param  \LaraGram\Contracts\Template\Factory  $factory
     * @param  string  $contents
     * @return string
     */
    protected function createTemple8TemplateFromString($factory, $contents)
    {
        $factory->addNamespace(
            '__components',
            $directory = Container::getInstance()['config']->get('template.compiled')
        );

        if (! is_file($templateFile = $directory.'/'.hash('xxh128', $contents).'.t8.php')) {
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            file_put_contents($templateFile, $contents);
        }

        return '__components::'.basename($templateFile, '.t8.php');
    }

    /**
     * Get the data that should be supplied to the template.
     *
     * @author Freek Van der Herten
     * @author Brent Roose
     *
     * @return array
     */
    public function data()
    {
        $this->attributes = $this->attributes ?: $this->newAttributeBag();

        return array_merge($this->extractPublicProperties(), $this->extractPublicMethods());
    }

    /**
     * Extract the public properties for the component.
     *
     * @return array
     */
    protected function extractPublicProperties()
    {
        $class = get_class($this);

        if (! isset(static::$propertyCache[$class])) {
            $reflection = new ReflectionClass($this);

            static::$propertyCache[$class] = (new Collection($reflection->getProperties(ReflectionProperty::IS_PUBLIC)))
                ->reject(fn (ReflectionProperty $property) => $property->isStatic())
                ->reject(fn (ReflectionProperty $property) => $this->shouldIgnore($property->getName()))
                ->map(fn (ReflectionProperty $property) => $property->getName())
                ->all();
        }

        $values = [];

        foreach (static::$propertyCache[$class] as $property) {
            $values[$property] = $this->{$property};
        }

        return $values;
    }

    /**
     * Extract the public methods for the component.
     *
     * @return array
     */
    protected function extractPublicMethods()
    {
        $class = get_class($this);

        if (! isset(static::$methodCache[$class])) {
            $reflection = new ReflectionClass($this);

            static::$methodCache[$class] = (new Collection($reflection->getMethods(ReflectionMethod::IS_PUBLIC)))
                ->reject(fn (ReflectionMethod $method) => $this->shouldIgnore($method->getName()))
                ->map(fn (ReflectionMethod $method) => $method->getName());
        }

        $values = [];

        foreach (static::$methodCache[$class] as $method) {
            $values[$method] = $this->createVariableFromMethod(new ReflectionMethod($this, $method));
        }

        return $values;
    }

    /**
     * Create a callable variable from the given method.
     *
     * @param  \ReflectionMethod  $method
     * @return mixed
     */
    protected function createVariableFromMethod(ReflectionMethod $method)
    {
        return $method->getNumberOfParameters() === 0
                        ? $this->createInvokableVariable($method->getName())
                        : Closure::fromCallable([$this, $method->getName()]);
    }

    /**
     * Create an invokable, toStringable variable for the given component method.
     *
     * @param  string  $method
     * @return \LaraGram\Template\InvokableComponentVariable
     */
    protected function createInvokableVariable(string $method)
    {
        return new InvokableComponentVariable(function () use ($method) {
            return $this->{$method}();
        });
    }

    /**
     * Determine if the given property / method should be ignored.
     *
     * @param  string  $name
     * @return bool
     */
    protected function shouldIgnore($name)
    {
        return str_starts_with($name, '__') ||
               in_array($name, $this->ignoredMethods());
    }

    /**
     * Get the methods that should be ignored.
     *
     * @return array
     */
    protected function ignoredMethods()
    {
        return array_merge([
            'data',
            'render',
            'resolve',
            'resolveTemplate',
            'shouldRender',
            'template',
            'withName',
            'withAttributes',
            'flushCache',
            'forgetFactory',
            'forgetComponentsResolver',
            'resolveComponentsUsing',
        ], $this->except);
    }

    /**
     * Set the component alias name.
     *
     * @param  string  $name
     * @return $this
     */
    public function withName($name)
    {
        $this->componentName = $name;

        return $this;
    }

    /**
     * Set the extra attributes that the component should make available.
     *
     * @param  array  $attributes
     * @return $this
     */
    public function withAttributes(array $attributes)
    {
        $this->attributes = $this->attributes ?: $this->newAttributeBag();

        $this->attributes->setAttributes($attributes);

        return $this;
    }

    /**
     * Get a new attribute bag instance.
     *
     * @param  array  $attributes
     * @return \LaraGram\Template\ComponentAttributeBag
     */
    protected function newAttributeBag(array $attributes = [])
    {
        return new ComponentAttributeBag($attributes);
    }

    /**
     * Determine if the component should be rendered.
     *
     * @return bool
     */
    public function shouldRender()
    {
        return true;
    }

    /**
     * Get the evaluated template contents for the given template.
     *
     * @param  string|null  $template
     * @param  \LaraGram\Contracts\Support\Arrayable|array  $data
     * @param  array  $mergeData
     * @return \LaraGram\Contracts\Template\Template
     */
    public function template($template, $data = [], $mergeData = [])
    {
        return $this->factory()->make($template, $data, $mergeData);
    }

    /**
     * Get the template factory instance.
     *
     * @return \LaraGram\Contracts\Template\Factory
     */
    protected function factory()
    {
        if (is_null(static::$factory)) {
            static::$factory = Container::getInstance()->make('template');
        }

        return static::$factory;
    }

    /**
     * Get the cached set of anonymous component constructor parameter names to exclude.
     *
     * @return array
     */
    public static function ignoredParameterNames()
    {
        if (! isset(static::$ignoredParameterNames[static::class])) {
            $constructor = (new ReflectionClass(
                static::class
            ))->getConstructor();

            if (! $constructor) {
                return static::$ignoredParameterNames[static::class] = [];
            }

            static::$ignoredParameterNames[static::class] = (new Collection($constructor->getParameters()))
                ->map
                ->getName()
                ->all();
        }

        return static::$ignoredParameterNames[static::class];
    }

    /**
     * Flush the component's cached state.
     *
     * @return void
     */
    public static function flushCache()
    {
        static::$Temple8TemplateCache = [];
        static::$constructorParametersCache = [];
        static::$methodCache = [];
        static::$propertyCache = [];
    }

    /**
     * Forget the component's factory instance.
     *
     * @return void
     */
    public static function forgetFactory()
    {
        static::$factory = null;
    }

    /**
     * Forget the component's resolver callback.
     *
     * @return void
     *
     * @internal
     */
    public static function forgetComponentsResolver()
    {
        static::$componentsResolver = null;
    }

    /**
     * Set the callback that should be used to resolve components within templates.
     *
     * @param  \Closure(string $component, array $data): Component  $resolver
     * @return void
     *
     * @internal
     */
    public static function resolveComponentsUsing($resolver)
    {
        static::$componentsResolver = $resolver;
    }
}
