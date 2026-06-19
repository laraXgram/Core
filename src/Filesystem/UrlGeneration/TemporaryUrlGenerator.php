<?php
declare(strict_types=1);

namespace LaraGram\Filesystem\UrlGeneration;

use DateTimeInterface;
use LaraGram\Filesystem\Config;
use LaraGram\Filesystem\Exception\UnableToGenerateTemporaryUrl;

interface TemporaryUrlGenerator
{
    /**
     * @throws UnableToGenerateTemporaryUrl
     */
    public function temporaryUrl(string $path, DateTimeInterface $expiresAt, Config $config): string;
}
