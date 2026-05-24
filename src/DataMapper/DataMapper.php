<?php

declare(strict_types=1);

namespace InitORM\DBAL\DataMapper;

use InitORM\DBAL\DataMapper\Exceptions\DataMapperException;
use InitORM\DBAL\DataMapper\Interfaces\DataMapperInterface;
use PDO;
use PDOStatement;

use function is_bool;
use function is_int;
use function is_null;
use function ltrim;

/**
 * Fluent wrapper around a single {@see PDOStatement}.
 *
 * Unknown method calls and property reads are forwarded to the underlying
 * statement. PDOStatement methods that return `$this` are re-wrapped to
 * return this DataMapper so chains span the wrapper boundary.
 *
 * @mixin PDOStatement
 */
class DataMapper implements DataMapperInterface
{
    private PDOStatement $statement;

    public function __construct(PDOStatement $statement)
    {
        $this->statement = $statement;
    }

    /**
     * @param array<int, mixed> $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        $result = $this->statement->{$name}(...$arguments);

        return $result instanceof PDOStatement ? $this : $result;
    }

    /**
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->statement->{$name};
    }

    public function __isset(string $name): bool
    {
        return isset($this->statement->{$name});
    }

    public function getStatement(): PDOStatement
    {
        return $this->statement;
    }

    public function execute(?array $params = null): bool
    {
        return $this->statement->execute($params);
    }

    public function getQuery(): string
    {
        return $this->statement->queryString;
    }

    public function bindValue(string $key, $value): bool
    {
        $key = ':' . ltrim($key, ':');

        return $this->statement->bindValue($key, $value, $this->bind($value));
    }

    public function bindValues(array $fields): bool
    {
        foreach ($fields as $key => $value) {
            if (!$this->bindValue((string) $key, $value)) {
                return false;
            }
        }

        return true;
    }

    public function bind($value): int
    {
        if (is_bool($value)) {
            return PDO::PARAM_BOOL;
        }
        if (is_int($value)) {
            return PDO::PARAM_INT;
        }
        if (is_null($value)) {
            return PDO::PARAM_NULL;
        }

        return PDO::PARAM_STR;
    }

    public function numRows(): int
    {
        return $this->statement->rowCount();
    }

    public function asClass(?string $class = null): self
    {
        $this->statement->setFetchMode(PDO::FETCH_CLASS, $class ?? \stdClass::class);

        return $this;
    }

    public function asObject(?object $obj = null): self
    {
        if ($obj !== null) {
            $this->statement->setFetchMode(PDO::FETCH_INTO, $obj);
        } else {
            $this->statement->setFetchMode(PDO::FETCH_OBJ);
        }

        return $this;
    }

    public function asAssoc(): self
    {
        $this->statement->setFetchMode(PDO::FETCH_ASSOC);

        return $this;
    }

    public function asLazy(): self
    {
        $this->statement->setFetchMode(PDO::FETCH_LAZY);

        return $this;
    }

    public function asArray(): self
    {
        $this->statement->setFetchMode(PDO::FETCH_BOTH);

        return $this;
    }

    public function asBoth(): self
    {
        return $this->asArray();
    }

    /**
     * @return array<string, mixed>|object|null
     */
    public function row()
    {
        $row = $this->statement->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @return array<int, array<string, mixed>|object>
     */
    public function rows(): array
    {
        $rows = $this->statement->fetchAll();

        return $rows === false ? [] : $rows;
    }
}
