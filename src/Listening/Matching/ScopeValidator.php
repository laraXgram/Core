<?php

namespace LaraGram\Listening\Matching;

use LaraGram\Request\Request;
use LaraGram\Listening\Listen;

class ScopeValidator implements ValidatorInterface
{
    /**
     * Validate a given rule against a listen and request.
     *
     * @param  \LaraGram\Listening\Listen  $listen
     * @param  \LaraGram\Request\Request  $request
     * @return bool
     */
    public function matches(Listen $listen, Request $request)
    {
        return in_array(strtolower($request->scope()), $listen->getScopeAttribute());
    }
}
