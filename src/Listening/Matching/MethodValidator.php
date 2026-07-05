<?php

namespace LaraGram\Listening\Matching;

use LaraGram\Listening\Contracts\ProvidesListenContext;
use LaraGram\Listening\Listen;

class MethodValidator implements ValidatorInterface
{
    /**
     * Validate a given rule against a listen and request.
     *
     * @param  \LaraGram\Listening\Listen  $listen
     * @param  \LaraGram\Listening\Contracts\ProvidesListenContext  $request
     * @return bool
     */
    public function matches(Listen $listen, ProvidesListenContext $request)
    {
        return in_array($request->listenVerb(), $listen->methods());
    }
}
