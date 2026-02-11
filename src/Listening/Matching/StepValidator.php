<?php

namespace LaraGram\Listening\Matching;

use LaraGram\Listening\Listen;
use LaraGram\Request\Request;
use LaraGram\Support\Facades\Step;

class StepValidator implements ValidatorInterface
{
    /**
     * Validate a given rule against a listen and request.
     *
     * Non-step listens always pass this validator. For step listens
     * the user's current step in cache must match the listen's
     * declared step name.
     *
     * @param  \LaraGram\Listening\Listen  $listen
     * @param  \LaraGram\Request\Request  $request
     * @return bool
     */
    public function matches(Listen $listen, Request $request): bool
    {
        $stepName = $listen->getStepName();

        // Non-step listens always pass.
        if ($stepName === null) {
            return true;
        }

        return Step::is($stepName);
    }
}
