<?php

declare(strict_types=1);

namespace LaraGram\Http\Client\Core;

use LaraGram\Http\Factory\StreamInterface;

final class InflateStream implements StreamInterface
{
    use StreamDecoratorTrait;

    /** @var StreamInterface */
    private $stream;

    public function __construct(StreamInterface $stream)
    {
        $resource = StreamWrapper::getResource($stream);
        // Specify window=15+32, so zlib will use header detection to both gzip (with header) and zlib data
        // See https://www.zlib.net/manual.html#Advanced definition of inflateInit2
        // "Add 32 to windowBits to enable zlib and gzip decoding with automatic header detection"
        // Default window size is 15.
        stream_filter_append($resource, 'zlib.inflate', STREAM_FILTER_READ, ['window' => 15 + 32]);
        $this->stream = $stream->isSeekable() ? new Stream($resource) : new NoSeekStream(new Stream($resource));
    }
}
