<?php

namespace LaraGram\Template;

use LaraGram\Container\Container;
use LaraGram\Support\ServiceProvider;
use LaraGram\Template\Compilers\Temple8Compiler;
use LaraGram\Template\Engines\CompilerEngine;
use LaraGram\Template\Engines\EngineResolver;
use LaraGram\Template\Engines\FileEngine;
use LaraGram\Template\Engines\PhpEngine;

class TemplateServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerFactory();
        $this->registerTemplateFinder();
        $this->registerTemple8Compiler();
        $this->registerEngineResolver();

        $this->app->terminating(static function () {
            Component::flushCache();
        });
    }

    /**
     * Register the template environment.
     *
     * @return void
     */
    public function registerFactory()
    {
        $this->app->singleton('template', function ($app) {
            // Next we need to grab the engine resolver instance that will be used by the
            // environment. The resolver will be used by an environment to get each of
            // the various engine implementations such as plain PHP or Temple8 engine.
            $resolver = $app['template.engine.resolver'];

            $finder = $app['template.finder'];

            $factory = $this->createFactory($resolver, $finder, $app['events']);

            // We will also set the container instance on this template environment since the
            // template composers may be classes registered in the container, which allows
            // for great testable, flexible composers for the application developer.
            $factory->setContainer($app);

            $factory->share('app', $app);

            $app->terminating(static function () {
                Component::forgetFactory();
            });

            return $factory;
        });
    }

    /**
     * Create a new Factory Instance.
     *
     * @param  \LaraGram\Template\Engines\EngineResolver  $resolver
     * @param  \LaraGram\Template\TemplateFinderInterface  $finder
     * @param  \LaraGram\Contracts\Events\Dispatcher  $events
     * @return \LaraGram\Template\Factory
     */
    protected function createFactory($resolver, $finder, $events)
    {
        return new Factory($resolver, $finder, $events);
    }

    /**
     * Register the template finder implementation.
     *
     * @return void
     */
    public function registerTemplateFinder()
    {
        $this->app->bind('template.finder', function ($app) {
            return new FileTemplateFinder($app['files'], $app['config']['template.paths']);
        });
    }

    /**
     * Register the Temple8 compiler implementation.
     *
     * @return void
     */
    public function registerTemple8Compiler()
    {
        $this->app->singleton('temple8.compiler', function ($app) {
            return tap(new Temple8Compiler(
                $app['files'],
                $app['config']['template.compiled'],
                $app['config']->get('template.relative_hash', false) ? $app->basePath() : '',
                $app['config']->get('template.cache', true),
                $app['config']->get('template.compiled_extension', 'php'),
            ), function ($Temple8) {
                $Temple8->component('dynamic-component', DynamicComponent::class);
            });
        });
    }

    /**
     * Register the engine resolver instance.
     *
     * @return void
     */
    public function registerEngineResolver()
    {
        $this->app->singleton('template.engine.resolver', function () {
            $resolver = new EngineResolver;

            // Next, we will register the various template engines with the resolver so that the
            // environment will resolve the engines needed for various templates based on the
            // extension of template file. We call a method for each of the template's engines.
            foreach (['file', 'php', 'temple8'] as $engine) {
                $this->{'register'.ucfirst($engine).'Engine'}($resolver);
            }

            return $resolver;
        });
    }

    /**
     * Register the file engine implementation.
     *
     * @param  \LaraGram\Template\Engines\EngineResolver  $resolver
     * @return void
     */
    public function registerFileEngine($resolver)
    {
        $resolver->register('file', function () {
            return new FileEngine(Container::getInstance()->make('files'));
        });
    }

    /**
     * Register the PHP engine implementation.
     *
     * @param  \LaraGram\Template\Engines\EngineResolver  $resolver
     * @return void
     */
    public function registerPhpEngine($resolver)
    {
        $resolver->register('php', function () {
            return new PhpEngine(Container::getInstance()->make('files'));
        });
    }

    /**
     * Register the Temple8 engine implementation.
     *
     * @param  \LaraGram\Template\Engines\EngineResolver  $resolver
     * @return void
     */
    public function registerTemple8Engine($resolver)
    {
        $resolver->register('temple8', function () {
            $app = Container::getInstance();

            $compiler = new CompilerEngine(
                $app->make('temple8.compiler'),
                $app->make('files'),
            );

            $app->terminating(static function () use ($compiler) {
                $compiler->forgetCompiledOrNotExpired();
            });

            return $compiler;
        });
    }
}
