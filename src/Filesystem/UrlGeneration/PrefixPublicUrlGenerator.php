<?php

declare(strict_types=1);

namespace LaraGram\Filesystem\UrlGeneration;

use LaraGram\Filesystem\Config;
use LaraGram\Filesystem\PathPrefixer;

class PrefixPublicUrlGenerator implements PublicUrlGenerator
{
    private PathPrefixer $prefixer;

    public function __construct(string $urlPrefix)
    {
        $this->prefixer = new PathPrefixer($urlPrefix, '/');
    }

    public function publicUrl(string $path, Config $config): string
    {
        return $this->prefixer->prefixPath($path);
    }
}
