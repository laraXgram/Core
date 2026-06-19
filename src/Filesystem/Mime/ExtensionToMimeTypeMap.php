<?php

declare(strict_types=1);

namespace LaraGram\Filesystem\Mime;

interface ExtensionToMimeTypeMap
{
    public function lookupMimeType(string $extension): ?string;
}
