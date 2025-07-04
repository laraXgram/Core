<?php

namespace LaraGram\Listening;

use LaraGram\Contracts\Listening\ResponseFactory;

class TemplateController extends Controller
{
    /**
     * The response factory implementation.
     *
     * @var \LaraGram\Contracts\Listening\ResponseFactory
     */
    protected $response;

    /**
     * Create a new controller instance.
     *
     * @param  \LaraGram\Contracts\Listening\ResponseFactory  $response
     * @return void
     */
    public function __construct(ResponseFactory $response)
    {
        $this->response = $response;
    }

    /**
     * Invoke the controller method.
     *
     * @param  mixed  ...$args
     * @return \LaraGram\Request\Response
     */
    public function __invoke(...$args)
    {
        $listenParameters = array_filter($args, function ($key) {
            return ! in_array($key, ['template', 'data']);
        }, ARRAY_FILTER_USE_KEY);

        $args['data'] = array_merge($args['data'], $listenParameters);

        return $this->response->template(
            $args['template'],
            $args['data']
        );
    }

    /**
     * Execute an action on the controller.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return \LaraGram\Request\Response
     */
    public function callAction($method, $parameters)
    {
        return $this->{$method}(...$parameters);
    }
}
