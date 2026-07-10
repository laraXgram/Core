<?php

declare(strict_types=1);

namespace LaraGram\Filesystem\Connections\Ftp;

interface ConnectionProvider
{
    /**
     * @return resource
     */
    public function createConnection(FtpConnectionOptions $options);
}
