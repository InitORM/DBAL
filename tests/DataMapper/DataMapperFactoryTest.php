<?php

declare(strict_types=1);

namespace InitORM\DBAL\Tests\DataMapper;

use InitORM\DBAL\DataMapper\DataMapperFactory;
use InitORM\DBAL\DataMapper\Interfaces\DataMapperInterface;
use InitORM\DBAL\Tests\Support\SqliteHelper;
use PHPUnit\Framework\TestCase;

final class DataMapperFactoryTest extends TestCase
{
    public function test_creates_data_mapper_from_pdo_statement(): void
    {
        $connection = SqliteHelper::makeConnection();
        SqliteHelper::seedUsers($connection);
        $stmt = $connection->getPDO()->prepare('SELECT 1');

        $mapper = (new DataMapperFactory())->createDataMapper($stmt);

        self::assertInstanceOf(DataMapperInterface::class, $mapper);
        self::assertSame($stmt, $mapper->getStatement());
    }
}
