<?php

declare(strict_types=1);

namespace LaraGram\Filesystem\Connections\Ftp;

interface ConnectivityChecker
{
    /**
     * @param resource $connection
     */
    public function isConnected($connection): bool;
}
