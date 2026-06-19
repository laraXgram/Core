<?php
declare(strict_types=1);

namespace LaraGram\Filesystem;

use LaraGram\Contracts\Filesystem\FilesystemException;
use LaraGram\Filesystem\Exception\UnableToProvideChecksum;
use function hash_final;
use function hash_init;
use function hash_update_stream;

trait CalculateChecksumFromStream
{
    private function calculateChecksumFromStream(string $path, Config $config): string
    {
        try {
            $stream = $this->readStream($path);
            $algo = (string) $config->get('checksum_algo', 'md5');
            $context = hash_init($algo);
            hash_update_stream($context, $stream);

            return hash_final($context);
        } catch (FilesystemException $exception) {
            throw new UnableToProvideChecksum($exception->getMessage(), $path, $exception);
        }
    }

    /**
     * @return resource
     */
    abstract public function readStream(string $path);
}
