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

use InitORM\DBAL\DataMapper\Interfaces\DataMapperInterface;
use PDO;
use PDOStatement;
use InitORM\DBAL\DataMapper\Exceptions\DataMapperException;

class DataMapper implements DataMapperInterface
{

    private PDOStatement $statement;

    public function __construct(PDOStatement $statement)
    {
        $this->statement = $statement;
    }

    /**
     * @throws DataMapperException
     */
    public function __call($name, $arguments)
    {
        $res = $this->getStatement()->{$name}(...$arguments);

        return ($res instanceof PDOStatement) ? $this : $res;
    }

    /**
     * @param $name
     * @return mixed
     * @throws DataMapperException
     */
    public function __get($name)
    {
        return $this->getStatement()->{$name};
    }

    /**
     * @param $name
     * @return bool
     * @throws DataMapperException
     */
    public function __isset($name)
    {
        return isset($this->getStatement()->{$name});
    }

    /**
     * @inheritDoc
     */
    public function getStatement(): PDOStatement
    {
        if (!isset($this->statement)) {
            throw new DataMapperException();
        }

        return $this->statement;
    }

    /**
     * @inheritDoc
     */
    public function execute(?array $params = null): bool
    {
        return $this->getStatement()->execute($params);
    }

    /**
     * @inheritDoc
     */
    public function getQuery(): string
    {
        return $this->getStatement()->queryString;
    }

    /**
     * @inheritDoc
     */
    public function bindValue(string $key, $value): bool
    {
        $key = ':' . ltrim($key, ':');

        return $this->getStatement()->bindValue($key, $value, $this->bind($value));
    }

    /**
     * @inheritDoc
     */
    public function bindValues(array $fields): bool
    {
        foreach ($fields as $key => $value) {
            $this->bindValue($key, $value);
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function bind($value): int
    {
        switch (true) {
            case is_int($value):
            case is_bool($value):
                return PDO::PARAM_INT;
            case is_null($value):
                return PDO::PARAM_NULL;
            default:
                return PDO::PARAM_STR;
        }
    }

    /**
     * @inheritDoc
     */
    public function numRows(): int
    {
        $count = $this->getStatement()->rowCount();

        return empty($count) ? 0 : $count;
    }

    /**
     * @inheritDoc
     */
    public function asClass(?string $class = null): self
    {
        $this->getStatement()->setFetchMode(PDO::FETCH_CLASS, $class);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function asObject(): self
    {
        $this->getStatement()->setFetchMode(PDO::FETCH_OBJ);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function asAssoc(): self
    {
        $this->getStatement()->setFetchMode(PDO::FETCH_ASSOC);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function asLazy(): self
    {
        $this->getStatement()->setFetchMode(PDO::FETCH_LAZY);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function asArray(): self
    {
        $this->getStatement()->setFetchMode(PDO::FETCH_BOTH);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function asBoth(): self
    {
        return $this->asArray();
    }

    /**
     * @inheritDoc
     */
    public function row()
    {
        $res = $this->getStatement()->fetch();

        return !empty($res) ? $res : null;
    }

    /**
     * @inheritDoc
     */
    public function rows(): ?array
    {
        $res = $this->getStatement()->fetchAll();

        return !empty($res) ? $res : null;
    }


}
