<?php

namespace LaraGram\Redis;

use Redis;
use RedisException;

class Connection
{
    private Redis $connection;
    public function __construct()
    {
        $this->connection = new Redis();
    }

    /**
     * @throws RedisException
     */
    public function connect(): void
    {
        $this->connection->connect(config()->get('database.REDIS_IP'), config()->get('database.REDIS_PORT'));
    }

    public function getConnection(): Redis
    {
        return $this->connection;
    }
}