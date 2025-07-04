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
     * Create a new JSON response instance.
     *
     * @param  mixed  $data
     * @param int $options
     * @return \LaraGram\Request\JsonResponse
     */
    public function json($data = [], $options = 0);
}
