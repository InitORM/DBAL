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

use InitORM\DBAL\DataMapper\Interfaces\DataMapperInterface;
use PDO;
use InitORM\DBAL\Connection\Exceptions\ConnectionException;
use InitORM\DBAL\Connection\Exceptions\ValidConnectionAvailableException;

interface ConnectionInterface
{

    /**
     * @param array $credentials
     */
    public function __construct(array $credentials = []);

    /**
     * @return self
     */
    public function clone(): self;

    /**
     * @return PDO
     * @throws ConnectionException
     */
    public function getPDO(): PDO;

    /**
     * @param string $sqlQuery
     * @param array|null $parameters
     * @param array|null $options
     * @return DataMapperInterface
     */
    public function query(string $sqlQuery, ?array $parameters = null, ?array $options = null): DataMapperInterface;

    /**
     * @return bool
     * @throws ConnectionException
     */
    public function connect(): bool;

    /**
     * @return bool
     */
    public function disconnect(): bool;

    /**
     * @param string $database
     * @return self
     * @throws ValidConnectionAvailableException
     */
    public function setDatabase(string $database): self;

    /**
     * @return string|null
     */
    public function getDatabase(): ?string;

    /**
     * @param string $host
     * @return self
     * @throws ValidConnectionAvailableException
     */
    public function setHost(string $host): self;

    /**
     * @return string|null
     */
    public function getHost(): ?string;

    /**
     * @param int|string $port
     * @return self
     * @throws ValidConnectionAvailableException
     */
    public function setPort($port): self;

    /**
     * @return string|int|null
     */
    public function getPort();

    /**
     * @param string $charset
     * @param string|null $collation <p>IS NULL => {$charset}_unicode_ci</p>
     * @return self
     * @throws ValidConnectionAvailableException
     */
    public function setCharset(string $charset = 'utf8mb4', ?string $collation = null): self;

    /**
     * @return string
     */
    public function getCharset(): string;

    /**
     * @return string
     */
    public function getCollation(): string;

    /**
     * @param string $dsn
     * @return self
     * @throws ValidConnectionAvailableException
     */
    public function setDsn(string $dsn): self;

    /**
     * @return string
     */
    public function getDsn(): string;

    /**
     * @param string|null $username
     * @return self
     * @throws ValidConnectionAvailableException
     */
    public function setUsername(?string $username): self;

    /**
     * @return string|null
     */
    public function getUsername(): ?string;

    /**
     * @param null|string $password
     * @return self
     * @throws ValidConnectionAvailableException
     */
    public function setPassword(?string $password = null): self;

    /**
     * @return string|null
     */
    public function getPassword(): ?string;

    /**
     * @param string $driver
     * @return self
     * @throws ValidConnectionAvailableException
     */
    public function setDriver(string $driver): self;

    /**
     * @return string
     */
    public function getDriver(): string;

    /**
     * @param array $options
     * @return self
     * @throws ValidConnectionAvailableException
     */
    public function setOptions(array $options = []): self;

    /**
     * @return array
     */
    public function getOptions(): array;

    /**
     * @param string $query
     * @param array|null $args
     * @param float|string $startTime <p>microtime(true)</p>
     * @return void
     */
    public function addQueryLog(string $query, ?array $args = null, $startTime = null): void;

    /**
     * @return array
     */
    public function getQueryLogs(): array;

    /**
     * @param bool $status
     * @return self
     */
    public function setQueryLogs(bool $status = false): self;

    /**
     * @param string $message
     * @param array|null $context
     * @return bool
     */
    public function createLog(string $message, ?array $context = null): bool;

    /**
     * @param bool $status
     * @return self
     */
    public function setDebug(bool $status = false): self;

    /**
     * @return bool
     */
    public function getDebug(): bool;
}
