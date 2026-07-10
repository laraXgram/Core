<?php

namespace LaraGram\Support\Facades;

use LaraGram\Support\Facades\Facade;

/**
 * @method static \LaraGram\Contracts\View\View file(string $path, \LaraGram\Contracts\Support\Arrayable|array $data = [], array $mergeData = [])
 * @method static \LaraGram\Contracts\View\View make(string $view, \LaraGram\Contracts\Support\Arrayable|array $data = [], array $mergeData = [])
 * @method static \LaraGram\Contracts\View\View first(array $views, \LaraGram\Contracts\Support\Arrayable|array $data = [], array $mergeData = [])
 * @method static string renderWhen(bool $condition, string $view, \LaraGram\Contracts\Support\Arrayable|array $data = [], array $mergeData = [])
 * @method static string renderUnless(bool $condition, string $view, \LaraGram\Contracts\Support\Arrayable|array $data = [], array $mergeData = [])
 * @method static string renderEach(string $view, array $data, string $iterator, string $empty = 'raw|')
 * @method static bool exists(string $view)
 * @method static \LaraGram\Contracts\View\Engine getEngineFromPath(string $path)
 * @method static mixed share(array|string $key, mixed $value = null)
 * @method static void incrementRender()
 * @method static void decrementRender()
 * @method static bool doneRendering()
 * @method static bool hasRenderedOnce(string $id)
 * @method static void markAsRenderedOnce(string $id)
 * @method static void addLocation(string $location)
 * @method static void prependLocation(string $location)
 * @method static \LaraGram\View\Factory addNamespace(string $namespace, string|array $hints)
 * @method static \LaraGram\View\Factory prependNamespace(string $namespace, string|array $hints)
 * @method static \LaraGram\View\Factory replaceNamespace(string $namespace, string|array $hints)
 * @method static void addExtension(string $extension, string $engine, \Closure|null $resolver = null)
 * @method static void flushState()
 * @method static void flushStateIfDoneRendering()
 * @method static array getExtensions()
 * @method static \LaraGram\View\Engines\EngineResolver getEngineResolver()
 * @method static \LaraGram\View\ViewFinderInterface getFinder()
 * @method static void setFinder(\LaraGram\View\ViewFinderInterface $finder)
 * @method static void flushFinderCache()
 * @method static \LaraGram\Contracts\Events\Dispatcher getDispatcher()
 * @method static void setDispatcher(\LaraGram\Contracts\Events\Dispatcher $events)
 * @method static \LaraGram\Contracts\Container\Container getContainer()
 * @method static void setContainer(\LaraGram\Contracts\Container\Container $container)
 * @method static mixed shared(string $key, mixed $default = null)
 * @method static array getShared()
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 * @method static void startComponent(\LaraGram\Contracts\View\View|\LaraGram\Contracts\Support\Htmlable|\Closure|string $view, array $data = [])
 * @method static void startComponentFirst(array $names, array $data = [])
 * @method static string renderComponent()
 * @method static mixed getConsumableComponentData(string $key, mixed $default = null)
 * @method static void slot(string $name, string|null $content = null, array $attributes = [])
 * @method static void endSlot()
 * @method static array creator(array|string $views, \Closure|string $callback)
 * @method static array composers(array $composers)
 * @method static array composer(array|string $views, \Closure|string $callback)
 * @method static void callComposer(\LaraGram\Contracts\View\View $view)
 * @method static void callCreator(\LaraGram\Contracts\View\View $view)
 * @method static void startFragment(string $fragment)
 * @method static string stopFragment()
 * @method static mixed getFragment(string $name, string|null $default = null)
 * @method static array getFragments()
 * @method static void flushFragments()
 * @method static void startSection(string $section, string|null $content = null)
 * @method static void inject(string $section, string $content)
 * @method static string yieldSection()
 * @method static string stopSection(bool $overwrite = false)
 * @method static string appendSection()
 * @method static string yieldContent(string $section, string $default = '')
 * @method static string parentPlaceholder(string $section = '')
 * @method static bool hasSection(string $name)
 * @method static bool sectionMissing(string $name)
 * @method static mixed getSection(string $name, string|null $default = null)
 * @method static array getSections()
 * @method static void flushSections()
 * @method static void addLoop(\Countable|array $data)
 * @method static void incrementLoopIndices()
 * @method static void popLoop()
 * @method static \stdClass|null getLastLoop()
 * @method static array getLoopStack()
 * @method static void startPush(string $section, string $content = '')
 * @method static string stopPush()
 * @method static void startPrepend(string $section, string $content = '')
 * @method static string stopPrepend()
 * @method static string yieldPushContent(string $section, string $default = '')
 * @method static bool isStackEmpty(string $section)
 * @method static void flushStacks()
 * @method static void startTranslation(array $replacements = [])
 * @method static string renderTranslation()
 *
 * @see \LaraGram\View\Factory
 */
class View extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'view';
    }
}
