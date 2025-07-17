<?php

namespace LaraGram\Foundation\Bootstrap;

use LaraGram\Contracts\Foundation\Application;
use LaraGram\Request\Request;

class SetRequestForConsole
{
    /**
     * Bootstrap the given application.
     *
     * @param  \LaraGram\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        $uri = $app->make('config')->get('app.url', 'http://localhost');

        $components = parse_url($uri);

        $server = $_SERVER;

        if (isset($components['path'])) {
            $server = array_merge($server, [
                'SCRIPT_FILENAME' => $components['path'],
                'SCRIPT_NAME' => $components['path'],
            ]);
        }

        $app->instance('request', Request::create(
            [], $server
        ));
    }
}
