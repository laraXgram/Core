<?php

namespace LaraGram\Listening;

use LaraGram\Request\RedirectResponse;
use LaraGram\Request\Request;
use LaraGram\Support\Collection;

class RedirectController extends Controller
{
    /**
     * Invoke the controller method.
     *
     * @param  \LaraGram\Request\Request  $request
     * @param  \LaraGram\Listening\PathGenerator  $path
     * @return \LaraGram\Request\RedirectResponse
     */
    public function __invoke(Request $request, PathGenerator $path)
    {
        $parameters = new Collection($request->listen()->parameters());

        $destination = $parameters->get('destination');

        $parameters->forget('destination');

        $listen = (new Listen('TEXT', $destination, [
            'as' => 'laragram_listen_redirect_destination',
        ]))->bind($request);

        $parameters = $parameters->only(
            $listen->getCompiled()->getVariables()
        )->all();

        $path = $path->toListen($listen, $parameters);

        return new RedirectResponse($path);
    }
}
