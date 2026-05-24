<?php

declare(strict_types=1);

namespace InitORM\DBAL\DataMapper;

use InitORM\DBAL\DataMapper\Interfaces\DataMapperFactoryInterface;
use InitORM\DBAL\DataMapper\Interfaces\DataMapperInterface;
use PDOStatement;

final class DataMapperFactory implements DataMapperFactoryInterface
{
    public function createDataMapper(PDOStatement $statement): DataMapperInterface
    {
        return new DataMapper($statement);
    }
}
