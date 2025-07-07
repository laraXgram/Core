<?php

namespace LaraGram\Support\Finder;

class SplFileInfo extends \SplFileInfo
{
    /**
     * @param string $file             The file name
     * @param string $relativePath     The relative path
     * @param string $relativePathname The relative path name
     */
    public function __construct(
        string $file,
        private string $relativePath,
        private string $relativePathname,
    ) {
        parent::__construct($file);
    }

    /**
     * Returns the relative path.
     *
     * This path does not contain the file name.
     */
    public function getRelativePath(): string
    {
        return $this->relativePath;
    }

    /**
     * Returns the relative path name.
     *
     * This path contains the file name.
     */
    public function getRelativePathname(): string
    {
        return $this->relativePathname;
    }

    public function getFilenameWithoutExtension(): string
    {
        $filename = $this->getFilename();

        return pathinfo($filename, \PATHINFO_FILENAME);
    }

    /**
     * Returns the contents of the file.
     *
     * @throws \RuntimeException
     */
    public function getContents(): string
    {
        set_error_handler(function ($type, $msg) use (&$error) { $error = $msg; });
        try {
            $content = file_get_contents($this->getPathname());
        } finally {
            restore_error_handler();
        }
        if (false === $content) {
            throw new \RuntimeException($error);
        }

        return $content;
    }
}
