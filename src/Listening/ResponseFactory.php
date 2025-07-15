<?php

namespace LaraGram\Listening;

use LaraGram\Contracts\Listening\ResponseFactory as FactoryContract;
use LaraGram\Contracts\Template\Factory as TemplateFactory;
use LaraGram\Request\JsonResponse;
use LaraGram\Request\Response;
use LaraGram\Support\Str;
use LaraGram\Support\Traits\Macroable;

class ResponseFactory implements FactoryContract
{
    use Macroable;

    /**
     * The template factory instance.
     *
     * @var \LaraGram\Contracts\Template\Factory
     */
    protected $template;

    /**
     * The redirector instance.
     *
     * @var \LaraGram\Listening\Redirector
     */
    protected $redirector;

    /**
     * Create a new response factory instance.
     *
     * @param  \LaraGram\Contracts\Template\Factory  $template
     * @param  \LaraGram\Listening\Redirector  $redirector
     * @return void
     */
    public function __construct(TemplateFactory $template, Redirector $redirector)
    {
        $this->template = $template;
        $this->redirector = $redirector;
    }

    /**
     * Create a new response instance.
     *
     * @param  mixed  $content
     * @return \LaraGram\Request\Response
     */
    public function make($content = '')
    {
        return new Response($content);
    }

    /**
     * Create a new "no content" response.
     *
     * @return \LaraGram\Request\Response
     */
    public function noContent()
    {
        return $this->make();
    }

    /**
     * Create a new response for a given template.
     *
     * @param  string|array  $template
     * @param  array  $data
     * @return \LaraGram\Request\Response
     */
    public function template($template, $data = [])
    {
        if (is_array($template)) {
            return $this->make($this->template->first($template, $data));
        }

        return $this->make($this->template->make($template, $data));
    }

    /**
     * Create a new JSON response instance.
     *
     * @param  mixed  $data
     * @param  int  $options
     * @return \LaraGram\Request\JsonResponse
     */
    public function json($data = [], $options = 0)
    {
        return new JsonResponse($data, $options);
    }

    /**
     * Convert the string to ASCII characters that are equivalent to the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function fallbackName($name)
    {
        return str_replace('%', '', Str::ascii($name));
    }

    /**
     * Create a new redirect response to the given path.
     *
     * @param  string  $path
     * @return \LaraGram\Request\RedirectResponse
     */
    public function redirectTo($path)
    {
        return $this->redirector->to($path);
    }

    /**
     * Create a new redirect response to a named listen.
     *
     * @param  \BackedEnum|string  $listen
     * @param  mixed  $parameters
     * @return \LaraGram\Request\RedirectResponse
     */
    public function redirectToListen($listen, $parameters = [])
    {
        return $this->redirector->listen($listen, $parameters);
    }

    /**
     * Create a new redirect response to a controller action.
     *
     * @param  array|string  $action
     * @param  mixed  $parameters
     * @return \LaraGram\Request\RedirectResponse
     */
    public function redirectToAction($action, $parameters = [])
    {
        return $this->redirector->action($action, $parameters);
    }
}
