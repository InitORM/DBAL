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
namespace InitORM\DBAL\DataMapper\Interfaces;

use InitORM\DBAL\DataMapper\Exceptions\DataMapperException;
use PDOStatement;

/**
 * @mixin PDOStatement
 */
interface DataMapperInterface
{

    public function __construct(PDOStatement $statement);

    /**
     * @return PDOStatement
     * @throws DataMapperException
     */
    public function getStatement(): PDOStatement;

    /**
     * @param array|null $params
     * @return bool
     * @throws DataMapperException
     */
    public function execute(?array $params = null): bool;


    /**
     * @return string
     * @throws DataMapperException
     */
    public function getQuery(): string;

    /**
     * @param string $key
     * @param mixed $value
     * @return bool
     * @throws DataMapperException
     */
    public function bindValue(string $key, $value): bool;

    /**
     * @param array $fields
     * @return bool
     * @throws DataMapperException
     */
    public function bindValues(array $fields): bool;

    /**
     * @param mixed $value
     * @return int
     * @throws DataMapperException
     */
    public function bind($value): int;

    /**
     * @return int
     * @throws DataMapperException
     * @see PDOStatement::rowCount()
     */
    public function numRows(): int;

    /**
     * @param string|null $class
     * @return self
     * @throws DataMapperException
     */
    public function asClass(?string $class = null): self;

    /**
     * @return self
     * @throws DataMapperException
     */
    public function asObject(): self;

    /**
     * @return self
     * @throws DataMapperException
     */
    public function asAssoc(): self;

    /**
     * @return self
     * @throws DataMapperException
     */
    public function asLazy(): self;

    /**
     * @return self
     * @throws DataMapperException
     */
    public function asArray(): self;

    /**
     * @return self
     * @throws DataMapperException
     * @see self::asArray()
     */
    public function asBoth(): self;

    /**
     * @return array|object|null
     * @throws DataMapperException
     */
    public function row();

    /**
     * @return array|object[]|null
     * @throws DataMapperException
     */
    public function rows(): ?array;

}
