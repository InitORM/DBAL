<?php

declare(strict_types=1);

namespace InitORM\DBAL\Connection\Interfaces;

use InitORM\DBAL\Connection\Exceptions\ConnectionAlreadyEstablishedException;

/**
 * Connection credentials surface — setters guard against mutation after the
 * underlying PDO has been instantiated and throw
 * {@see ConnectionAlreadyEstablishedException} in that case.
 */
interface ConfigurableConnectionInterface
{
    /**
     * @throws ConnectionAlreadyEstablishedException
     */
    public function setDatabase(string $database): self;

    public function getDatabase(): ?string;

    /**
     * @throws ConnectionAlreadyEstablishedException
     */
    public function setHost(string $host): self;

    public function getHost(): ?string;

    /**
     * @param int|string $port
     * @throws ConnectionAlreadyEstablishedException
     */
    public function setPort($port): self;

    /**
     * @return int|string|null
     */
    public function getPort();

    /**
     * @param string|null $collation If null the database default is used; otherwise
     *                               must match `[A-Za-z0-9_]+`.
     * @throws ConnectionAlreadyEstablishedException
     */
    public function setCharset(string $charset = 'utf8mb4', ?string $collation = null): self;

    public function getCharset(): string;

    public function getCollation(): ?string;

    /**
     * @throws ConnectionAlreadyEstablishedException
     */
    public function setDsn(string $dsn): self;

    public function getDsn(): string;

    /**
     * @throws ConnectionAlreadyEstablishedException
     */
    public function setUsername(?string $username): self;

    public function getUsername(): ?string;

    /**
     * @throws ConnectionAlreadyEstablishedException
     */
    public function setPassword(?string $password = null): self;

    public function getPassword(): ?string;

    /**
     * @throws ConnectionAlreadyEstablishedException
     */
    public function setDriver(string $driver): self;

    public function getDriver(): string;

    /**
     * @param array<int, mixed> $options PDO attribute map (integer keys).
     * @throws ConnectionAlreadyEstablishedException
     */
    public function setOptions(array $options = []): self;

    /**
     * @return array<int, mixed>
     */
    public function getOptions(): array;
}
