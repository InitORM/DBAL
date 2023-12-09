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
namespace InitORM\DBAL\Connection\Interfaces;

interface ConnectionFactoryInterface
{

    /**
     * @param array $credentials
     * @return ConnectionInterface
     */
    public function createConnection(array $credentials = []): ConnectionInterface;

}
