<?php

namespace LaraGram\Contracts\Routing;

use Closure;
use LaraGram\Http\StreamedEvent;

interface ResponseFactory
{
    /**
     * Create a new response instance.
     *
     * @param  array|string  $content
     * @param  int  $status
     * @param  array  $headers
     * @return \LaraGram\Http\Response
     */
    public function make($content = '', $status = 200, array $headers = []);

    /**
     * Create a new "no content" response.
     *
     * @param  int  $status
     * @param  array  $headers
     * @return \LaraGram\Http\Response
     */
    public function noContent($status = 204, array $headers = []);

    /**
     * Create a new response for a given view.
     *
     * @param  string|array  $view
     * @param  array  $data
     * @param  int  $status
     * @param  array  $headers
     * @return \LaraGram\Http\Response
     */
    public function view($view, $data = [], $status = 200, array $headers = []);

    /**
     * Create a new JSON response instance.
     *
     * @param  mixed  $data
     * @param  int  $status
     * @param  array  $headers
     * @param  int  $options
     * @return \LaraGram\Http\JsonResponse
     */
    public function json($data = [], $status = 200, array $headers = [], $options = 0);

    /**
     * Create a new JSONP response instance.
     *
     * @param  string  $callback
     * @param  mixed  $data
     * @param  int  $status
     * @param  array  $headers
     * @param  int  $options
     * @return \LaraGram\Http\JsonResponse
     */
    public function jsonp($callback, $data = [], $status = 200, array $headers = [], $options = 0);

    /**
     * Create a new event stream response.
     *
     * @param  \Closure  $callback
     * @param  array  $headers
     * @param  \LaraGram\Http\StreamedEvent|string|null  $endStreamWith
     * @return \LaraGram\Http\StreamedResponse
     */
    public function eventStream(Closure $callback, array $headers = [], StreamedEvent|string|null $endStreamWith = '</stream>');

    /**
     * Create a new streamed response instance.
     *
     * @param  callable  $callback
     * @param  int  $status
     * @param  array  $headers
     * @return \LaraGram\Http\StreamedResponse
     */
    public function stream($callback, $status = 200, array $headers = []);

    /**
     * Create a new streamed JSON response instance.
     *
     * @param  array  $data
     * @param  int  $status
     * @param  array  $headers
     * @param  int  $encodingOptions
     * @return \LaraGram\Http\StreamedJsonResponse
     */
    public function streamJson($data, $status = 200, $headers = [], $encodingOptions = 15);

    /**
     * Create a new streamed response instance as a file download.
     *
     * @param  callable  $callback
     * @param  string|null  $name
     * @param  array  $headers
     * @param  string|null  $disposition
     * @return \LaraGram\Http\StreamedResponse
     */
    public function streamDownload($callback, $name = null, array $headers = [], $disposition = 'attachment');

    /**
     * Create a new file download response.
     *
     * @param  \SplFileInfo|string  $file
     * @param  string|null  $name
     * @param  array  $headers
     * @param  string|null  $disposition
     * @return \LaraGram\Http\BinaryFileResponse
     */
    public function download($file, $name = null, array $headers = [], $disposition = 'attachment');

    /**
     * Return the raw contents of a binary file.
     *
     * @param  \SplFileInfo|string  $file
     * @param  array  $headers
     * @return \LaraGram\Http\BinaryFileResponse
     */
    public function file($file, array $headers = []);

    /**
     * Create a new redirect response to the given path.
     *
     * @param  string  $path
     * @param  int  $status
     * @param  array  $headers
     * @param  bool|null  $secure
     * @return \LaraGram\Http\RedirectResponse
     */
    public function redirectTo($path, $status = 302, $headers = [], $secure = null);

    /**
     * Create a new redirect response to a named route.
     *
     * @param  \BackedEnum|string  $route
     * @param  mixed  $parameters
     * @param  int  $status
     * @param  array  $headers
     * @return \LaraGram\Http\RedirectResponse
     */
    public function redirectToRoute($route, $parameters = [], $status = 302, $headers = []);

    /**
     * Create a new redirect response to a controller action.
     *
     * @param  array|string  $action
     * @param  mixed  $parameters
     * @param  int  $status
     * @param  array  $headers
     * @return \LaraGram\Http\RedirectResponse
     */
    public function redirectToAction($action, $parameters = [], $status = 302, $headers = []);

    /**
     * Create a new redirect response, while putting the current URL in the session.
     *
     * @param  string  $path
     * @param  int  $status
     * @param  array  $headers
     * @param  bool|null  $secure
     * @return \LaraGram\Http\RedirectResponse
     */
    public function redirectGuest($path, $status = 302, $headers = [], $secure = null);

    /**
     * Create a new redirect response to the previously intended location.
     *
     * @param  string  $default
     * @param  int  $status
     * @param  array  $headers
     * @param  bool|null  $secure
     * @return \LaraGram\Http\RedirectResponse
     */
    public function redirectToIntended($default = '/', $status = 302, $headers = [], $secure = null);
}
