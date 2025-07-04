<?php

namespace LaraGram\Template\Concerns;

use Closure;
use LaraGram\Contracts\Template\Template as TemplateContract;
use LaraGram\Support\Str;

trait ManagesEvents
{
    /**
     * Register a template creator event.
     *
     * @param  array|string  $templates
     * @param  \Closure|string  $callback
     * @return array
     */
    public function creator($templates, $callback)
    {
        $creators = [];

        foreach ((array) $templates as $template) {
            $creators[] = $this->addTemplateEvent($template, $callback, 'creating: ');
        }

        return $creators;
    }

    /**
     * Register multiple template composers via an array.
     *
     * @param  array  $composers
     * @return array
     */
    public function composers(array $composers)
    {
        $registered = [];

        foreach ($composers as $callback => $templates) {
            $registered = array_merge($registered, $this->composer($templates, $callback));
        }

        return $registered;
    }

    /**
     * Register a template composer event.
     *
     * @param  array|string  $templates
     * @param  \Closure|string  $callback
     * @return array
     */
    public function composer($templates, $callback)
    {
        $composers = [];

        foreach ((array) $templates as $template) {
            $composers[] = $this->addTemplateEvent($template, $callback);
        }

        return $composers;
    }

    /**
     * Add an event for a given template.
     *
     * @param  string  $template
     * @param  \Closure|string  $callback
     * @param  string  $prefix
     * @return \Closure|null
     */
    protected function addTemplateEvent($template, $callback, $prefix = 'composing: ')
    {
        $template = $this->normalizeName($template);

        if ($callback instanceof Closure) {
            $this->addEventListener($prefix.$template, $callback);

            return $callback;
        } elseif (is_string($callback)) {
            return $this->addClassEvent($template, $callback, $prefix);
        }
    }

    /**
     * Register a class based template composer.
     *
     * @param  string  $template
     * @param  string  $class
     * @param  string  $prefix
     * @return \Closure
     */
    protected function addClassEvent($template, $class, $prefix)
    {
        $name = $prefix.$template;

        // When registering a class based template "composer", we will simply resolve the
        // classes from the application IoC container then call the compose method
        // on the instance. This allows for convenient, testable template composers.
        $callback = $this->buildClassEventCallback(
            $class, $prefix
        );

        $this->addEventListener($name, $callback);

        return $callback;
    }

    /**
     * Build a class based container callback Closure.
     *
     * @param  string  $class
     * @param  string  $prefix
     * @return \Closure
     */
    protected function buildClassEventCallback($class, $prefix)
    {
        [$class, $method] = $this->parseClassEvent($class, $prefix);

        // Once we have the class and method name, we can build the Closure to resolve
        // the instance out of the IoC container and call the method on it with the
        // given arguments that are passed to the Closure as the composer's data.
        return function () use ($class, $method) {
            return $this->container->make($class)->{$method}(...func_get_args());
        };
    }

    /**
     * Parse a class based composer name.
     *
     * @param  string  $class
     * @param  string  $prefix
     * @return array
     */
    protected function parseClassEvent($class, $prefix)
    {
        return Str::parseCallback($class, $this->classEventMethodForPrefix($prefix));
    }

    /**
     * Determine the class event method based on the given prefix.
     *
     * @param  string  $prefix
     * @return string
     */
    protected function classEventMethodForPrefix($prefix)
    {
        return str_contains($prefix, 'composing') ? 'compose' : 'create';
    }

    /**
     * Add a listener to the event dispatcher.
     *
     * @param  string  $name
     * @param  \Closure  $callback
     * @return void
     */
    protected function addEventListener($name, $callback)
    {
        if (str_contains($name, '*')) {
            $callback = function ($name, array $data) use ($callback) {
                return $callback($data[0]);
            };
        }

        $this->events->listen($name, $callback);
    }

    /**
     * Call the composer for a given template.
     *
     * @param  \LaraGram\Contracts\Template\Template  $template
     * @return void
     */
    public function callComposer(TemplateContract $template)
    {
        if ($this->events->hasListeners($event = 'composing: '.$template->name())) {
            $this->events->dispatch($event, [$template]);
        }
    }

    /**
     * Call the creator for a given template.
     *
     * @param  \LaraGram\Contracts\Template\Template  $template
     * @return void
     */
    public function callCreator(TemplateContract $template)
    {
        if ($this->events->hasListeners($event = 'creating: '.$template->name())) {
            $this->events->dispatch($event, [$template]);
        }
    }
}
