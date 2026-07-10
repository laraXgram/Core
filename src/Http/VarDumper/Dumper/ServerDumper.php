<?php

namespace LaraGram\Http\VarDumper\Dumper;

use  LaraGram\Http\VarDumper\Cloner\Data;
use  LaraGram\Http\VarDumper\Dumper\ContextProvider\ContextProviderInterface;
use  LaraGram\Http\VarDumper\Server\Connection;

/**
 * ServerDumper forwards serialized Data clones to a server.
 *
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
class ServerDumper implements DataDumperInterface
{
    private Connection $connection;

    /**
     * @param string                     $host             The server host
     * @param DataDumperInterface|null   $wrappedDumper    A wrapped instance used whenever we failed contacting the server
     * @param ContextProviderInterface[] $contextProviders Context providers indexed by context name
     */
    public function __construct(
        string $host,
        private ?DataDumperInterface $wrappedDumper = null,
        array $contextProviders = [],
    ) {
        $this->connection = new Connection($host, $contextProviders);
    }

    public function getContextProviders(): array
    {
        return $this->connection->getContextProviders();
    }

    public function dump(Data $data): ?string
    {
        if (!$this->connection->write($data) && $this->wrappedDumper) {
            return $this->wrappedDumper->dump($data);
        }

        return null;
    }
}
