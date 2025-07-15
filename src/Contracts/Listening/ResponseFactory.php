<?php

namespace LaraGram\Contracts\Listening;

interface ResponseFactory
{
    /**
     * Create a new response instance.
     *
     * @param  array|string  $content
     * @return \LaraGram\Request\Response
     */
    public function make($content = '');

    /**
     * Create a new "no content" response.
     *
     * @return \LaraGram\Request\Response
     */
    public function noContent();

    /**
     * Create a new response for a given template.
     *
     * @param  string|array  $template
     * @param  array  $data
     * @return \LaraGram\Request\Response
     */
    public function template($template, $data = []);


    /**
     * Create a new JSON response instance.
     *
     * @param  mixed  $data
     * @param int $options
     * @return \LaraGram\Request\JsonResponse
     */
    public function json($data = [], $options = 0);

    /**
     * Create a new redirect response to a named listen.
     *
     * @param  \BackedEnum|string  $listen
     * @param  mixed  $parameters
     * @return \LaraGram\Request\RedirectResponse
     */
    public function redirectToListen($listen, $parameters = []);

    /**
     * Create a new redirect response to a controller action.
     *
     * @param  array|string  $action
     * @param  mixed  $parameters
     * @return \LaraGram\Request\RedirectResponse
     */
    public function redirectToAction($action, $parameters = []);
}
