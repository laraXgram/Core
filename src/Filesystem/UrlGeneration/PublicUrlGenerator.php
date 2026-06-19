<?php

declare(strict_types=1);

namespace LaraGram\Filesystem\UrlGeneration;

use LaraGram\Filesystem\Config;
use LaraGram\Filesystem\Exception\UnableToGeneratePublicUrl;

interface PublicUrlGenerator
{
    /**
     * @throws UnableToGeneratePublicUrl
     */
    public function publicUrl(string $path, Config $config): string;
}
