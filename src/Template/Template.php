<?php

namespace LaraGram\Template;

use ArrayAccess;
use BadMethodCallException;
use LaraGram\Contracts\Support\Arrayable;
use LaraGram\Contracts\Support\Htmlable;
use LaraGram\Contracts\Support\MessageProvider;
use LaraGram\Contracts\Support\Renderable;
use LaraGram\Contracts\Template\Engine;
use LaraGram\Contracts\Template\Template as TemplateContract;
use LaraGram\Support\Collection;
use LaraGram\Support\MessageBag;
use LaraGram\Support\Str;
use LaraGram\Support\Traits\Macroable;
use LaraGram\Support\TemplateErrorBag;
use Stringable;
use Throwable;

class Template implements ArrayAccess, Htmlable, Stringable, TemplateContract
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * The template factory instance.
     *
     * @var \LaraGram\Template\Factory
     */
    protected $factory;

    /**
     * The engine implementation.
     *
     * @var \LaraGram\Contracts\Template\Engine
     */
    protected $engine;

    /**
     * The name of the template.
     *
     * @var string
     */
    protected $template;

    /**
     * The array of template data.
     *
     * @var array
     */
    protected $data;

    /**
     * The path to the template file.
     *
     * @var string
     */
    protected $path;

    /**
     * Create a new template instance.
     *
     * @param  \LaraGram\Template\Factory  $factory
     * @param  \LaraGram\Contracts\Template\Engine  $engine
     * @param  string  $template
     * @param  string  $path
     * @param  mixed  $data
     * @return void
     */
    public function __construct(Factory $factory, Engine $engine, $template, $path, $data = [])
    {
        $this->template = $template;
        $this->path = $path;
        $this->engine = $engine;
        $this->factory = $factory;

        $this->data = $data instanceof Arrayable ? $data->toArray() : (array) $data;
    }

    /**
     * Get the evaluated contents of a given fragment.
     *
     * @param  string  $fragment
     * @return string
     */
    public function fragment($fragment)
    {
        return $this->render(function () use ($fragment) {
            return $this->factory->getFragment($fragment);
        });
    }

    /**
     * Get the evaluated contents for a given array of fragments or return all fragments.
     *
     * @param  array|null  $fragments
     * @return string
     */
    public function fragments(?array $fragments = null)
    {
        return is_null($fragments)
            ? $this->allFragments()
            : (new Collection($fragments))->map(fn ($f) => $this->fragment($f))->implode('');
    }

    /**
     * Get the evaluated contents of a given fragment if the given condition is true.
     *
     * @param  bool  $boolean
     * @param  string  $fragment
     * @return string
     */
    public function fragmentIf($boolean, $fragment)
    {
        if (value($boolean)) {
            return $this->fragment($fragment);
        }

        return $this->render();
    }

    /**
     * Get the evaluated contents for a given array of fragments if the given condition is true.
     *
     * @param  bool  $boolean
     * @param  array|null  $fragments
     * @return string
     */
    public function fragmentsIf($boolean, ?array $fragments = null)
    {
        if (value($boolean)) {
            return $this->fragments($fragments);
        }

        return $this->render();
    }

    /**
     * Get all fragments as a single string.
     *
     * @return string
     */
    protected function allFragments()
    {
        return (new Collection($this->render(fn () => $this->factory->getFragments())))->implode('');
    }

    /**
     * Get the string contents of the template.
     *
     * @param  callable|null  $callback
     * @return string
     *
     * @throws \Throwable
     */
    public function render(?callable $callback = null)
    {
        try {
            $contents = $this->renderContents();

            $response = isset($callback) ? $callback($this, $contents) : null;

            // Once we have the contents of the template, we will flush the sections if we are
            // done rendering all templates so that there is nothing left hanging over when
            // another template gets rendered in the future by the application developer.
            $this->factory->flushStateIfDoneRendering();

            $result = ! is_null($response) ? $response : $contents;

            if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $result, $matches)) {
                $jsonPart = $matches[0];

                $data = json_decode($jsonPart, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    return json_encode($data);
                } else {
                    return json_last_error_msg();
                }
            }

            return $result;
        } catch (Throwable $e) {
            $this->factory->flushState();

            throw $e;
        }
    }

    /**
     * Get the contents of the template instance.
     *
     * @return string
     */
    protected function renderContents()
    {
        // We will keep track of the number of templates being rendered so we can flush
        // the section after the complete rendering operation is done. This will
        // clear out the sections for any separate templates that may be rendered.
        $this->factory->incrementRender();

        $this->factory->callComposer($this);

        $contents = $this->getContents();

        // Once we've finished rendering the template, we'll decrement the render count
        // so that each section gets flushed out next time a template is created and
        // no old sections are staying around in the memory of an environment.
        $this->factory->decrementRender();

        return $contents;
    }

    /**
     * Get the evaluated contents of the template.
     *
     * @return string
     */
    protected function getContents()
    {
        return $this->engine->get($this->path, $this->gatherData());
    }

    /**
     * Get the data bound to the template instance.
     *
     * @return array
     */
    public function gatherData()
    {
        $data = array_merge($this->factory->getShared(), $this->data);

        foreach ($data as $key => $value) {
            if ($value instanceof Renderable) {
                $data[$key] = $value->render();
            }
        }

        return $data;
    }

    /**
     * Get the sections of the rendered template.
     *
     * @return array
     *
     * @throws \Throwable
     */
    public function renderSections()
    {
        return $this->render(function () {
            return $this->factory->getSections();
        });
    }

    /**
     * Add a piece of data to the template.
     *
     * @param  string|array  $key
     * @param  mixed  $value
     * @return $this
     */
    public function with($key, $value = null)
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }

    /**
     * Add a template instance to the template data.
     *
     * @param  string  $key
     * @param  string  $template
     * @param  array  $data
     * @return $this
     */
    public function nest($key, $template, array $data = [])
    {
        return $this->with($key, $this->factory->make($template, $data));
    }

    /**
     * Add validation errors to the template.
     *
     * @param  \LaraGram\Contracts\Support\MessageProvider|array|string  $provider
     * @param  string  $bag
     * @return $this
     */
    public function withErrors($provider, $bag = 'default')
    {
        return $this->with('errors', (new TemplateErrorBag)->put(
            $bag, $this->formatErrors($provider)
        ));
    }

    /**
     * Parse the given errors into an appropriate value.
     *
     * @param  \LaraGram\Contracts\Support\MessageProvider|array|string  $provider
     * @return \LaraGram\Support\MessageBag
     */
    protected function formatErrors($provider)
    {
        return $provider instanceof MessageProvider
                        ? $provider->getMessageBag()
                        : new MessageBag((array) $provider);
    }

    /**
     * Get the name of the template.
     *
     * @return string
     */
    public function name()
    {
        return $this->getName();
    }

    /**
     * Get the name of the template.
     *
     * @return string
     */
    public function getName()
    {
        return $this->template;
    }

    /**
     * Get the array of template data.
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Get the path to the template file.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set the path to the template.
     *
     * @param  string  $path
     * @return void
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * Get the template factory instance.
     *
     * @return \LaraGram\Template\Factory
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * Get the template's rendering engine.
     *
     * @return \LaraGram\Contracts\Template\Engine
     */
    public function getEngine()
    {
        return $this->engine;
    }

    /**
     * Determine if a piece of data is bound.
     *
     * @param  string  $key
     * @return bool
     */
    public function offsetExists($key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Get a piece of bound data to the template.
     *
     * @param  string  $key
     * @return mixed
     */
    public function offsetGet($key): mixed
    {
        return $this->data[$key];
    }

    /**
     * Set a piece of data on the template.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($key, $value): void
    {
        $this->with($key, $value);
    }

    /**
     * Unset a piece of data from the template.
     *
     * @param  string  $key
     * @return void
     */
    public function offsetUnset($key): void
    {
        unset($this->data[$key]);
    }

    /**
     * Get a piece of data from the template.
     *
     * @param  string  $key
     * @return mixed
     */
    public function &__get($key)
    {
        return $this->data[$key];
    }

    /**
     * Set a piece of data on the template.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->with($key, $value);
    }

    /**
     * Check if a piece of data is bound to the template.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->data[$key]);
    }

    /**
     * Remove a piece of bound data from the template.
     *
     * @param  string  $key
     * @return void
     */
    public function __unset($key)
    {
        unset($this->data[$key]);
    }

    /**
     * Dynamically bind parameters to the template.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return \LaraGram\Template\Template
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        if (! str_starts_with($method, 'with')) {
            throw new BadMethodCallException(sprintf(
                'Method %s::%s does not exist.', static::class, $method
            ));
        }

        return $this->with(Str::camel(substr($method, 4)), $parameters[0]);
    }

    /**
     * Get content as a string of HTML.
     *
     * @return string
     */
    public function toHtml()
    {
        return $this->render();
    }

    /**
     * Get the string contents of the template.
     *
     * @return string
     *
     * @throws \Throwable
     */
    public function __toString(): string
    {
        return $this->render();
    }
}
