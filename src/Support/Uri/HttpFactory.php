<?php

namespace LaraGram\Support\Uri;

use LaraGram\Http\Factory\UriFactoryInterface;
use LaraGram\Http\Factory\UriInterface;

final class HttpFactory implements UriFactoryInterface
{
    public function createUri(string $uri = ''): UriInterface
    {
        return Http::new($uri);
    }
}
