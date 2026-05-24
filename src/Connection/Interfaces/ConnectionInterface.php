<?php

declare(strict_types=1);

namespace InitORM\DBAL\Connection\Interfaces;

use InitORM\DBAL\Connection\Exceptions\ConnectionException;
use InitORM\DBAL\Connection\Exceptions\SQLExecuteException;
use InitORM\DBAL\DataMapper\Interfaces\DataMapperInterface;
use PDO;

/**
 * Lifecycle and query-execution surface of a Connection.
 *
 * Inherits all credentials accessors from {@see ConfigurableConnectionInterface}
 * and logging operations from {@see LoggableConnectionInterface}, so a single
 * type-hint on this interface still covers the full Connection API.
 */
interface ConnectionInterface extends ConfigurableConnectionInterface, LoggableConnectionInterface
{
    /**
     * Return a fresh, disconnected Connection that carries the same
     * credentials as `$this`.
     */
    public function clone(): self;

    /**
     * Lazily instantiate and return the underlying PDO instance.
     *
     * @throws ConnectionException When PDO cannot connect with the configured credentials.
     */
    public function getPDO(): PDO;

    /**
     * Open the PDO connection using the currently configured credentials.
     *
     * Most callers do not need to invoke this directly — {@see self::getPDO()}
     * and {@see self::query()} call it on demand.
     *
     * @throws ConnectionException
     */
    public function connect(): bool;

    /**
     * Release the in-process reference to the underlying PDO.
     *
     * Note: with `PDO::ATTR_PERSISTENT => true` the connection is returned
     * to the pool rather than closed.
     */
    public function disconnect(): bool;

    /**
     * Prepare and execute `$sqlQuery` with optional named parameters and PDO
     * prepare options, returning a {@see DataMapperInterface} wrapping the
     * resulting statement.
     *
     * @param array<string, scalar|null>|null $parameters Named parameters; the
     *        leading `:` on each key is optional.
     * @param array<int, mixed>|null $options PDO prepare options (integer keys).
     *
     * @throws SQLExecuteException When prepare/execute fails.
     * @throws ConnectionException
     */
    public function query(string $sqlQuery, ?array $parameters = null, ?array $options = null): DataMapperInterface;
}
