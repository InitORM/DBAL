<?php

declare(strict_types=1);

namespace InitORM\DBAL\Tests\Connection\Exceptions;

use InitORM\DBAL\Connection\Exceptions\ConnectionAlreadyEstablishedException;
use InitORM\DBAL\Connection\Exceptions\ConnectionException;
use InitORM\DBAL\Connection\Exceptions\SQLExecuteException;
use InitORM\DBAL\Connection\Exceptions\ValidConnectionAvailableException;
use PHPUnit\Framework\TestCase;

final class ExceptionHierarchyTest extends TestCase
{
    public function test_sql_execute_extends_connection(): void
    {
        self::assertInstanceOf(ConnectionException::class, new SQLExecuteException());
    }

    public function test_already_established_extends_connection(): void
    {
        self::assertInstanceOf(ConnectionException::class, new ConnectionAlreadyEstablishedException());
    }

    /**
     * Backwards-compatible alias for callers that still catch the old name.
     * The new (preferred) class extends the old one, so old `catch` clauses
     * continue to match instances thrown by the library.
     */
    public function test_new_name_extends_deprecated_alias(): void
    {
        self::assertInstanceOf(ValidConnectionAvailableException::class, new ConnectionAlreadyEstablishedException());
    }
}
