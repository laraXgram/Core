<?php

namespace LaraGram\Listening\Navigation;

use Closure;
use LaraGram\Contracts\Support\Responsable;
use LaraGram\Request\RedirectResponse;

class BackResponse extends RedirectResponse implements Responsable
{
    /**
     * Indicates if the user has already been taken back.
     *
     * @var bool
     */
    protected $navigated = false;

    /**
     * Create a new back response.
     *
     * @param  \LaraGram\Listening\Navigation\Navigator  $navigator
     * @param  int|string  $to  The number of steps, or the name of a step.
     * @param  \Closure|string|null  $fallback  What to run when there is nothing to go back to.
     * @return void
     */
    public function __construct(
        protected Navigator $navigator,
        protected int|string $to = 1,
        protected Closure|string|null $fallback = null,
    ) {
        parent::__construct('back');
    }

    /**
     * Take the user back and get the response of the step they land on.
     *
     * @param  \LaraGram\Request\Request  $request
     * @return \LaraGram\Request\Response
     */
    public function toResponse($request)
    {
        if ($this->navigated) {
            return $this;
        }

        $this->navigated = true;

        return $this->navigator->navigate($this->to, $this->fallback, $request);
    }

    /**
     * Take the user back when the response was not returned from the handler.
     *
     * @return void
     */
    public function __destruct()
    {
        if (! $this->navigated) {
            $this->toResponse(app('request'));
        }
    }
}
