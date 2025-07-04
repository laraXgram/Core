<?php

namespace LaraGram\Contracts\Listening;

interface Registrar
{
    /**
     * Create a listen group with shared attributes.
     *
     * @param  array  $attributes
     * @param  \Closure|string  $listens
     * @return void
     */
    public function group(array $attributes, $listens);

    /**
     * Substitute the listen bindings onto the listen.
     *
     * @param  \LaraGram\Listening\Listen  $listen
     * @return \LaraGram\Listening\Listen
     */
    public function substituteBindings($listen);

    /**
     * Substitute the implicit Eloquent model bindings for the listen.
     *
     * @param  \LaraGram\Listening\Listen  $listen
     * @return void
     */
    public function substituteImplicitBindings($listen);
}
