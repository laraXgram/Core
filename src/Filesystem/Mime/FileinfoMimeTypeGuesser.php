<?php

namespace LaraGram\Filesystem\Mime;

use LaraGram\Filesystem\Exception\InvalidArgumentException;
use LaraGram\Filesystem\Exception\LogicException;
use LaraGram\Filesystem\Exception\RuntimeException;

class FileinfoMimeTypeGuesser implements MimeTypeGuesserInterface
{
    /**
     * @var array<string, \finfo>
     */
    private static $finfoCache = [];

    /**
     * @param string|null $magicFile A magic file to use with the finfo instance
     *
     * @see http://www.php.net/manual/en/function.finfo-open.php
     */
    public function __construct(
        private ?string $magicFile = null,
    ) {
    }

    public function isGuesserSupported(): bool
    {
        return \function_exists('finfo_open');
    }

    public function guessMimeType(string $path): ?string
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new InvalidArgumentException(\sprintf('The "%s" file does not exist or is not readable.', $path));
        }

        if (!$this->isGuesserSupported()) {
            throw new LogicException(\sprintf('The "%s" guesser is not supported.', __CLASS__));
        }

        try {
            $finfo = self::$finfoCache[$this->magicFile] ??= new \finfo(\FILEINFO_MIME_TYPE, $this->magicFile);
        } catch (\Exception $e) {
            throw new RuntimeException($e->getMessage());
        }
        $mimeType = $finfo->file($path) ?: null;

        if ($mimeType && 0 === (\strlen($mimeType) % 2)) {
            $mimeStart = substr($mimeType, 0, \strlen($mimeType) >> 1);
            $mimeType = $mimeStart.$mimeStart === $mimeType ? $mimeStart : $mimeType;
        }

        return $mimeType;
    }
}
