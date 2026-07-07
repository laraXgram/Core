<?php

namespace LaraGram\Http\Factory;

use LaraGram\Http\Files\Exceptions\FileException;
use LaraGram\Http\Files\File;
use LaraGram\Http\Files\UploadedFile as BaseUploadedFile;

class UploadedFile extends BaseUploadedFile
{
    private bool $test = false;

    /**
     * @param-immediately-invoked-callable $getTemporaryPath
     */
    public function __construct(
        private readonly UploadedFileInterface $psrUploadedFile,
        callable $getTemporaryPath,
    ) {
        $error = $psrUploadedFile->getError();
        $path = '';

        if (\UPLOAD_ERR_NO_FILE !== $error) {
            $path = $psrUploadedFile->getStream()->getMetadata('uri') ?? '';

            if ($this->test = !\is_string($path) || !is_uploaded_file($path)) {
                $path = $getTemporaryPath();
                $psrUploadedFile->moveTo($path);
            }
        }

        parent::__construct(
            $path,
            (string) $psrUploadedFile->getClientFilename(),
            $psrUploadedFile->getClientMediaType(),
            $psrUploadedFile->getError(),
            $this->test
        );
    }

    public function move(string $directory, ?string $name = null): File
    {
        if (!$this->isValid() || $this->test) {
            return parent::move($directory, $name);
        }

        $target = $this->getTargetFile($directory, $name);

        try {
            $this->psrUploadedFile->moveTo((string) $target);
        } catch (\RuntimeException $e) {
            throw new FileException(\sprintf('Could not move the file "%s" to "%s" (%s).', $this->getPathname(), $target, $e->getMessage()), 0, $e);
        }

        @chmod($target, 0o666 & ~umask());

        return $target;
    }
}
