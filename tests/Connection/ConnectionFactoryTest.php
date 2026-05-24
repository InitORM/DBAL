<?php

declare(strict_types=1);

namespace InitORM\DBAL\Tests\Connection;

use InitORM\DBAL\Connection\ConnectionFactory;
use InitORM\DBAL\Connection\Interfaces\ConnectionInterface;
use PHPUnit\Framework\TestCase;

final class ConnectionFactoryTest extends TestCase
{
    public function test_creates_connection_from_credentials(): void
    {
        $connection = (new ConnectionFactory())->createConnection([
            'driver'   => 'sqlite',
            'database' => ':memory:',
        ]);

        self::assertInstanceOf(ConnectionInterface::class, $connection);
        self::assertSame('sqlite', $connection->getDriver());
    }
}
