<?php

namespace LaraGram\Contracts\Http;

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
     * @param  \LaraGram\Http\BaseRequest  $request
     * @return \LaraGram\Http\BaseResponse
     */
    public function handle($request);

    /**
     * Perform any final actions for the request lifecycle.
     *
     * @param  \LaraGram\Http\BaseRequest  $request
     * @param  \LaraGram\Http\BaseResponse  $response
     * @return void
     */
    public function terminate($request, $response);

    /**
     * Get the LaraGram application instance.
     *
     * @return \LaraGram\Contracts\Foundation\Application
     */
    public function getApplication();
}
