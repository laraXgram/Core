<?php

namespace LaraGram\Foundation\Bootstrap;

use LaraGram\Foundation\Application;
use LaraGram\Foundation\AliasLoader;
use LaraGram\Foundation\PackageManifest;
use LaraGram\Support\Facades\Facade;

class RegisterFacades
{
    public function bootstrap(Application $app)
    {
        Facade::clearResolvedInstances();

        Facade::setFacadeApplication($app);

        AliasLoader::getInstance(array_merge(
            $app->make('config')->get('app.aliases', []),
            $app->make(PackageManifest::class)->aliases()
        ))->register();
    }
}
