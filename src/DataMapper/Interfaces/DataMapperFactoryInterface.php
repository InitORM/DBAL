<?php

declare(strict_types=1);

namespace InitORM\DBAL\DataMapper\Interfaces;

use PDOStatement;

interface DataMapperFactoryInterface
{
    public function createDataMapper(PDOStatement $statement): DataMapperInterface;
}
