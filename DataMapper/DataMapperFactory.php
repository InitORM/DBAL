<?php
/**
 * InitORM DBAL
 *
 * This file is part of InitORM DBAL.
 *
 * @author      Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright   Copyright © 2023 Muhammet ŞAFAK
 * @license     ./LICENSE  MIT
 * @version     1.0
 * @link        https://www.muhammetsafak.com.tr
 */

declare(strict_types=1);
namespace InitORM\DBAL\DataMapper;

use InitORM\DBAL\DataMapper\Interfaces\DataMapperFactoryInterface;
use InitORM\DBAL\DataMapper\Interfaces\DataMapperInterface;
use PDOStatement;

class DataMapperFactory implements Interfaces\DataMapperFactoryInterface
{

    public function createDataMapper(PDOStatement $statement): DataMapperInterface
    {
        return new DataMapper($statement);
    }

}
