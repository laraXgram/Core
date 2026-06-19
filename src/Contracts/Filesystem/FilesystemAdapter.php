<?php

declare(strict_types=1);

namespace LaraGram\Contracts\Filesystem;

use LaraGram\Filesystem\Config;
use LaraGram\Filesystem\Exception\InvalidVisibilityProvided;
use LaraGram\Filesystem\Exception\UnableToCheckExistence;
use LaraGram\Filesystem\Exception\UnableToCopyFile;
use LaraGram\Filesystem\Exception\UnableToCreateDirectory;
use LaraGram\Filesystem\Exception\UnableToDeleteDirectory;
use LaraGram\Filesystem\Exception\UnableToDeleteFile;
use LaraGram\Filesystem\Exception\UnableToMoveFile;
use LaraGram\Filesystem\Exception\UnableToReadFile;
use LaraGram\Filesystem\Exception\UnableToRetrieveMetadata;
use LaraGram\Filesystem\Exception\UnableToWriteFile;
use LaraGram\Filesystem\FileAttributes;
use LaraGram\Filesystem\StorageAttributes;

interface FilesystemAdapter
{
    /**
     * @throws FilesystemException
     * @throws UnableToCheckExistence
     */
    public function fileExists(string $path): bool;

    /**
     * @throws FilesystemException
     * @throws UnableToCheckExistence
     */
    public function directoryExists(string $path): bool;

    /**
     * @throws UnableToWriteFile
     * @throws FilesystemException
     */
    public function write(string $path, string $contents, Config $config): void;

    /**
     * @param resource $contents
     *
     * @throws UnableToWriteFile
     * @throws FilesystemException
     */
    public function writeStream(string $path, $contents, Config $config): void;

    /**
     * @throws UnableToReadFile
     * @throws FilesystemException
     */
    public function read(string $path): string;

    /**
     * @return resource
     *
     * @throws UnableToReadFile
     * @throws FilesystemException
     */
    public function readStream(string $path);

    /**
     * @throws UnableToDeleteFile
     * @throws FilesystemException
     */
    public function delete(string $path): void;

    /**
     * @throws UnableToDeleteDirectory
     * @throws FilesystemException
     */
    public function deleteDirectory(string $path): void;

    /**
     * @throws UnableToCreateDirectory
     * @throws FilesystemException
     */
    public function createDirectory(string $path, Config $config): void;

    /**
     * @throws InvalidVisibilityProvided
     * @throws FilesystemException
     */
    public function setVisibility(string $path, string $visibility): void;

    /**
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemException
     */
    public function visibility(string $path): FileAttributes;

    /**
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemException
     */
    public function mimeType(string $path): FileAttributes;

    /**
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemException
     */
    public function lastModified(string $path): FileAttributes;

    /**
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemException
     */
    public function fileSize(string $path): FileAttributes;

    /**
     * @return iterable<StorageAttributes>
     *
     * @throws FilesystemException
     */
    public function listContents(string $path, bool $deep): iterable;

    /**
     * @throws UnableToMoveFile
     * @throws FilesystemException
     */
    public function move(string $source, string $destination, Config $config): void;

    /**
     * @throws UnableToCopyFile
     * @throws FilesystemException
     */
    public function copy(string $source, string $destination, Config $config): void;
}
