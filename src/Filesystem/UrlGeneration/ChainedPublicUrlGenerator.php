<?php

declare(strict_types=1);

namespace LaraGram\Filesystem\UrlGeneration;

use LaraGram\Filesystem\Config;
use LaraGram\Filesystem\Exception\UnableToGeneratePublicUrl;

final class ChainedPublicUrlGenerator implements PublicUrlGenerator
{
    /**
     * @param PublicUrlGenerator[] $generators
     */
    public function __construct(private iterable $generators)
    {
    }

    public function publicUrl(string $path, Config $config): string
    {
        foreach ($this->generators as $generator) {
            try {
                return $generator->publicUrl($path, $config);
            } catch (UnableToGeneratePublicUrl) {
            }
        }

        throw new UnableToGeneratePublicUrl('No supported public url generator found.', $path);
    }
}
