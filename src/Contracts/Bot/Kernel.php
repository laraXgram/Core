<?php

namespace LaraGram\Contracts\Bot;

interface Kernel
{
    /**
     * Bootstrap the application for HTTP requests.
     *
     * @return void
     */
    public function bootstrap();

    /**
     * Handle an incoming HTTP request.
     *
     * @param  \LaraGram\Request\Request  $request
     * @return \LaraGram\Request\Response
     */
    public function handle($request);

    /**
     * Perform any final actions for the request lifecycle.
     *
     * @param \LaraGram\Request\Request $request
     * @param  \LaraGram\Request\Response  $response
     * @return void
     */
    public function terminate($request, $response);

    /**
     * Get the Laravel application instance.
     *
     * @return \LaraGram\Contracts\Foundation\Application
     */
    public function getApplication();
}
