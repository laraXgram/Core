<?php

namespace LaraGram\Filesystem;

use DateTimeInterface;
use LaraGram\Contracts\Filesystem\ChecksumProvider;;
use LaraGram\Contracts\Filesystem\FilesystemAdapter as FilesystemAdapterContract;
use LaraGram\Filesystem\Exception\UnableToCopyFile;
use LaraGram\Filesystem\Exception\UnableToCreateDirectory;
use LaraGram\Filesystem\Exception\UnableToDeleteDirectory;
use LaraGram\Filesystem\Exception\UnableToDeleteFile;
use LaraGram\Filesystem\Exception\UnableToGeneratePublicUrl;
use LaraGram\Filesystem\Exception\UnableToGenerateTemporaryUrl;
use LaraGram\Filesystem\Exception\UnableToMoveFile;
use LaraGram\Filesystem\Exception\UnableToSetVisibility;
use LaraGram\Filesystem\Exception\UnableToWriteFile;
use LaraGram\Filesystem\UrlGeneration\PublicUrlGenerator;
use LaraGram\Filesystem\UrlGeneration\TemporaryUrlGenerator;

class ReadOnlyFilesystemAdapter extends DecoratedAdapter implements FilesystemAdapterContract, PublicUrlGenerator, ChecksumProvider, TemporaryUrlGenerator
{
    use CalculateChecksumFromStream;

    public function write(string $path, string $contents, Config $config): void
    {
        throw UnableToWriteFile::atLocation($path, 'This is a readonly adapter.');
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        throw UnableToWriteFile::atLocation($path, 'This is a readonly adapter.');
    }

    public function delete(string $path): void
    {
        throw UnableToDeleteFile::atLocation($path, 'This is a readonly adapter.');
    }

    public function deleteDirectory(string $path): void
    {
        throw UnableToDeleteDirectory::atLocation($path, 'This is a readonly adapter.');
    }

    public function createDirectory(string $path, Config $config): void
    {
        throw UnableToCreateDirectory::atLocation($path, 'This is a readonly adapter.');
    }

    public function setVisibility(string $path, string $visibility): void
    {
        throw UnableToSetVisibility::atLocation($path, 'This is a readonly adapter.');
    }

    public function move(string $source, string $destination, Config $config): void
    {
        throw new UnableToMoveFile("Unable to move file from $source to $destination as this is a readonly adapter.");
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        throw new UnableToCopyFile("Unable to copy file from $source to $destination as this is a readonly adapter.");
    }

    public function publicUrl(string $path, Config $config): string
    {
        if ( ! $this->adapter instanceof PublicUrlGenerator) {
            throw UnableToGeneratePublicUrl::noGeneratorConfigured($path);
        }

        return $this->adapter->publicUrl($path, $config);
    }

    public function checksum(string $path, Config $config): string
    {
        if ($this->adapter instanceof ChecksumProvider) {
            return $this->adapter->checksum($path, $config);
        }

        return $this->calculateChecksumFromStream($path, $config);
    }

    public function temporaryUrl(string $path, DateTimeInterface $expiresAt, Config $config): string
    {
        if ( ! $this->adapter instanceof TemporaryUrlGenerator) {
            throw UnableToGenerateTemporaryUrl::noGeneratorConfigured($path);
        }

        return $this->adapter->temporaryUrl($path, $expiresAt, $config);
    }
}
