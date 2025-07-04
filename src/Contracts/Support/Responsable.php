<?php

namespace LaraGram\Contracts\Support;

interface Responsable
{
    /**
     * Create an response that represents the object.
     *
     * @param  \LaraGram\Request\Request  $request
     * @return \LaraGram\Request\Response
     */
    public function toResponse($request);
}
