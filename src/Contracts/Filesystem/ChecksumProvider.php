<?php

namespace LaraGram\Contracts\Filesystem;

use LaraGram\Filesystem\Config;
use LaraGram\Filesystem\Exception\ChecksumAlgoIsNotSupported;
use LaraGram\Filesystem\Exception\UnableToProvideChecksum;

interface ChecksumProvider
{
    /**
     * @return string MD5 hash of the file contents
     *
     * @throws UnableToProvideChecksum
     * @throws ChecksumAlgoIsNotSupported
     */
    public function checksum(string $path, Config $config): string;
}
