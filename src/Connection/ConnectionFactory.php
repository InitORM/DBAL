<?php

declare(strict_types=1);

namespace InitORM\DBAL\Connection;

use InitORM\DBAL\Connection\Interfaces\ConnectionFactoryInterface;
use InitORM\DBAL\Connection\Interfaces\ConnectionInterface;

final class ConnectionFactory implements ConnectionFactoryInterface
{
    public function createConnection(array $credentials = []): ConnectionInterface
    {
        return new Connection($credentials);
    }
}
